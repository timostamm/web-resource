<?php

namespace TS\Web\Resource;


use Symfony\Component\Mime\MimeTypes;
use TS\Web\Resource\Exception\IOException;
use TS\Web\Resource\Exception\InvalidArgumentException;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class UrlResource implements ResourceInterface, TemporaryResourceInterface
{

	const DEFAULT_CONNECT_TIMEOUT = 20;

	private $timeout = self::DEFAULT_CONNECT_TIMEOUT;

	private $url;

	private $headRequested = false;

	private $bodyRequested = false;

	private $bodyTempFile;

	private $disposed = false;

	private $filename;

	private $mimetype;

	private $length;

	private $lastModified;

	private $hash;

    private $attributes;

	use OptionsTrait;

	/**
	 *
	 * @param string $url
	 * @param array $options
	 *        	Accepts the following options:
	 *        	- timeout: Set a custom timeout for the HTTP connection. Default value is 20 seconds.
	 *        	- filename: Overrides any automatically inferred filename.
	 *        	- lastmodified: Override the inferred date.
	 *        	- mimetype: Override the mimetype.
	 *        	- hash: Override the hash.
	 *          - attributes: Set optional attributes.
	 *
	 */
	public function __construct($url, array $options = [])
	{
		if (empty($url)) {
			throw new InvalidArgumentException('Missing argument "url".');
		}
		$this->url = $url;
		$this->timeout = $this->takeOption('timeout', $options, self::DEFAULT_CONNECT_TIMEOUT);
		$this->filename = $this->takeOption('filename', $options);
		$this->lastModified = $this->takeOption('lastmodified', $options);
		$this->mimetype = $this->takeOption('mimetype', $options);
		$this->hash = $this->takeOption('hash', $options);
		$this->attributes = $this->takeOption('attributes', $options, []);
		$this->denyRemainingOptions($options);
	}

	public function download($directory)
	{
		$path = $directory . DIRECTORY_SEPARATOR . $this->getFilename();
		if (file_exists($path)) {
			throw new InvalidArgumentException(sprintf('File "%s" already exists.', $path));
		}

		file_put_contents($path, $this->getStream());

		$res = new FileResource($path, [
			'mimetype' => $this->getMimetype(),
			'lastmodified' => $this->getLastModified()
		]);

		return $res;
	}

	public function downloadAs($file)
	{
		if (file_exists($file)) {
			throw new InvalidArgumentException(sprintf('File "%s" already exists.', $file));
		}
		file_put_contents($file, $this->getStream());
		$res = new FileResource($file, [
			'mimetype' => $this->getMimetype(),
			'lastmodified' => $this->getLastModified(),
			'filename' => $this->getFilename()
		]);
		return $res;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getFilename()
	 */
	public function getFilename()
	{
		if ($this->filename == null) {
			$this->requestHead();
		}
		if ($this->filename == null) {
			$filename = pathinfo($this->url)['basename'];
			if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                $mimeTypes = new MimeTypes();
				$ext = $mimeTypes->getExtensions(explode(';', $this->getMimetype())[0])[0] ?? null;
				if ($ext && $filename) {
					$filename .= '.' . $ext;
				}
			}
			$this->filename = $filename;
		}
		return $this->filename;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getMimetype()
	 */
	public function getMimetype()
	{
		if ($this->mimetype == null) {
			$this->requestHead();
		}
		return $this->mimetype == null ? 'application/octet-stream' : $this->mimetype;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLength()
	 */
	public function getLength()
	{
		if ($this->length == null) {
			$this->requestHead();
		}
		if ($this->length == null) {
			$this->requestBody();
		}
		return $this->length;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLastModified()
	 */
	public function getLastModified()
	{
		if ($this->lastModified == null) {
			$this->requestHead();
		}
		return $this->lastModified == null ? new \DateTime() : $this->lastModified;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getHash()
	 */
	public function getHash()
	{
		if ($this->hash == null) {
			$this->requestBody();
			$this->hash = sha1_file($this->bodyTempFile);
		}
		return $this->hash;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getStream()
	 */
	public function getStream($context = null)
	{
		$this->requestBody();
		if (is_null($context)) {
			return fopen($this->bodyTempFile, 'rb', false);
		} else {
			return fopen($this->bodyTempFile, 'rb', false, $context);
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * {@inheritdoc}
	 * @see ResourceInterface::getAttributes()
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}


	public function __toString()
	{
		return sprintf('[UrlResource %s]', $this->url);
	}

	public function dispose()
	{
		$f = $this->bodyTempFile;
		if (file_exists($f)) {
			unlink($f);
		}
		$d = dirname($f);
		if (file_exists($d)) {
			rmdir($d);
		}
		$this->disposed = true;
	}

	public function __destruct()
	{
		if (! $this->disposed) {
			$this->dispose();
		}
	}

	private function requestBody()
	{
		if ($this->bodyRequested) {
			return;
		}
		$this->bodyRequested = true;

		$this->bodyTempFile = ResourceUtil::createTempDir() . $this->getFilename();

		$out = fopen($this->bodyTempFile, 'w');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FILE, $out);
		$ok = curl_exec($ch);
		if (! $ok) {
			$msg = sprintf('Got HTTP %s for URL "%s".', curl_getinfo($ch, CURLINFO_HTTP_CODE), $this->url);
			throw new IOException($msg);
		}
		fclose($out);
		curl_close($ch);
	}

	private function requestHead()
	{
		if ($this->headRequested) {
			return;
		}
		$this->headRequested = true;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);

		$a = [];

		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerline) use (&$a) {
			$m = [];
			preg_match('/Content-Disposition: (?:attachment|inline); filename="(.*)"/i', $headerline, $m);
			if (count($m) > 0) {
				$a['filename'] = $m[1];
			}
			$m = [];
			preg_match('/Last-Modified: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$a['lastModified'] = \DateTime::createFromFormat('D, d M Y H:i:s O+', $m[1]);
			}
			$m = [];
			preg_match('/Date: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0 && $this->lastModified == null) {
				$a['date'] = \DateTime::createFromFormat('D, d M Y H:i:s O+', $m[1]);
			}
			$m = [];
			preg_match('/Content-Type: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$a['mimetype'] = $m[1];
			}
			$m = [];
			preg_match('/Content-Length: ([0-9]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$a['length'] = (int) $m[1];
			}
			return strlen($headerline);
		});

		$ok = curl_exec($ch);
		if (! $ok) {
			$msg = sprintf('Got HTTP %s for URL "%s".', curl_getinfo($ch, CURLINFO_HTTP_CODE), $this->url);
			throw new IOException($msg);
		}
		curl_close($ch);

		if ($this->filename == null && isset($a['filename'])) {
			$val = $a['filename'];
			$val = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
			$this->filename = $val;
		}
		if ($this->lastModified == null) {
			$this->lastModified = isset($a['lastModified']) ? $a['lastModified'] : $a['date'];
		}
		if ($this->mimetype == null) {
			$val = isset($a['mimetype']) ? $a['mimetype'] : null;
			$this->mimetype = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		if ($this->length == null && isset($a['length'])) {
			$this->length = isset($a['length']) ? $a['length'] : null;

		}
	}

}

