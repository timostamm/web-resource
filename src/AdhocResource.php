<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class AdhocResource implements ResourceInterface
{

	public static function fromUrl($url)
	{
		$attributes = [
			'filename' => pathinfo($url)['basename'], 
			'length' => null, 
			'stream' => function () use ($url) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$body = curl_exec($ch);
				curl_close($ch);
				$stream = fopen('php://memory', 'r+');
				fwrite($stream, $body);
				rewind($stream);
				return $stream;
			}
		];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerline) use (&$attributes) {
			$m = [];
			preg_match('/Content-Disposition: (?:attachment|inline); filename="(.*)"/i', $headerline, $m);
			if (count($m) > 0) {
				$attributes['filename'] = $m[1];
			}
			$m = [];
			preg_match('/Last-Modified: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$attributes['lastmodified'] = \DateTime::createFromFormat('D, d M Y H:i:s O+', $m[1]);
			}
			$m = [];
			preg_match('/Date: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0 && ! isset($attributes['lastmodified'])) {
				$attributes['lastmodified'] = \DateTime::createFromFormat('D, d M Y H:i:s O+', $m[1]);
			}
			$m = [];
			preg_match('/Content-Type: ([^\\r]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$attributes['mimetype'] = $m[1];
			}
			$m = [];
			preg_match('/Content-Length: ([0-9]+)/i', $headerline, $m);
			if (count($m) > 0) {
				$attributes['length'] = (int) $m[1];
			}
			return strlen($headerline);
		});
		$ok = curl_exec($ch);
		if (!$ok) {
			$msg = sprintf('Got HTTP %s for URL "%s".', curl_getinfo($ch, CURLINFO_HTTP_CODE), $url);
			throw new \Exception($msg);
		}
		curl_close($ch);
		return new AdhocResource($attributes);
	}

	private $content;

	private $streamFn;

	private $filename;

	private $mimetype;

	private $length;

	private $lastModified;

	private $hash;

	/**
	 *
	 * @param \Closure $streamFn
	 * @param array $attributes
	 */
	public function __construct(array $attributes)
	{
		$this->requireAttributes($attributes, [
			'filename',
			'mimetype'
		]);
		if (array_key_exists('stream', $attributes) && array_key_exists('content', $attributes)) {
			throw new \InvalidArgumentException('Attribute "stream" and "content" are exclusive.');
		}
		if (! array_key_exists('stream', $attributes) && ! array_key_exists('content', $attributes)) {
			throw new \InvalidArgumentException('Missing attribute "stream" or "content".');
		}
		if (array_key_exists('content', $attributes)) {
			$attributes['length'] = strlen($attributes['content']);
		} else {
			$this->requireAttributes($attributes, [
				'length'
			]);
		}
		$this->acceptAttributes($attributes);
	}

	private function requireAttributes(array & $attributes, array $required)
	{
		$missing = [];
		foreach ($required as $r) {
			if (array_key_exists($r, $attributes) == false) {
				$missing[] = $r;
			}
		}
		if (count($missing) > 0) {
			throw new \InvalidArgumentException(sprintf('Missing attributes "%s"', implode('", "', $missing)));
		}
	}

	private function acceptAttributes(array & $attributes)
	{
		
		foreach ($attributes as $key => $val) {
			switch ($key) {
				
				case 'content':
					if (! is_string($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be string but got %s.', $key, gettype($val)));
					}
					$this->content = $val;
					break;
				
				case 'stream':
					if (! is_callable($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be callable but got %s.', $key, gettype($val)));
					}
					$this->streamFn = $val;
					break;
				
				case 'filename':
					if (! is_string($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new \InvalidArgumentException(sprintf('Attribute "%s" is empty.', $key));
					}
					$this->filename = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					break;
				
				case 'length':
					if (! is_int($val) && ! is_null($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be of type int but got %s.', $key, gettype($val)));
					}
					if ($val < 0) {
						throw new \InvalidArgumentException(sprintf('Invalid attribute "%s": %s.', $key, $val));
					}
					$this->length = $val;
					break;
				
				case 'lastmodified':
					if (! $val instanceof \DateTime) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be a DateTime but got %s.', $key, gettype($val)));
					}
					$this->lastModified = $val;
					break;
				
				case 'mimetype':
					if (! is_string($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new \InvalidArgumentException(sprintf('Attribute "%s" is empty.', $key));
					}
					$this->mimetype = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					break;
				
				case 'hash':
					if (! is_string($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new \InvalidArgumentException(sprintf('Attribute "%s" is empty.', $key));
					}
					$this->hash = $val;
					break;
				
				default:
					throw new \InvalidArgumentException(sprintf('Unknown attribute "%s".', $key));
			}
		}
	
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getFilename()
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getMimetype()
	 */
	public function getMimetype()
	{
		return $this->mimetype;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLength()
	 */
	public function getLength()
	{
		return $this->length;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLastModified()
	 */
	public function getLastModified()
	{
		if (is_null($this->lastModified)) {
			return new \DateTime();
		}
		return $this->lastModified;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getHash()
	 */
	public function getHash()
	{
		if (is_null($this->hash)) {
			if (is_null($this->content)) {
				$this->content = stream_get_contents($this->getStream());
			}
			$this->hash = sha1($this->content);
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
		if (is_string($this->content)) {
			$stream = fopen('php://memory', 'r+');
			fwrite($stream, $this->content);
			rewind($stream);
			return $stream;
		}
		$fn = $this->streamFn;
		return $fn($context);
	}

	public function __toString()
	{
		return ResourceUtil::format($this);
	}

}

