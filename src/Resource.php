<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class Resource implements ResourceInterface
{

	public static function createTemp($filename = null, $mimetype = null, \DateTime $lastmodified = null)
	{
		return new TempResource($filename, $mimetype, $lastmodified);
	}

	public static function fromFile($path, array $attributes = null)
	{
		return new LocalResource($path, $attributes);
	}

	public static function fromUrl($url, array $attributes = null)
	{
		return new UrlResource($url, $attributes);
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
			if ($this->content != null) {
				$this->hash = sha1($this->content);
			} else {
				$path = ResourceUtil::createTempDir() . DIRECTORY_SEPARATOR . $this->getFilename();
				file_put_contents($path, $this->getStream());
				$this->hash = sha1_file($path);
				unlink($path);
				rmdir(dirname($path));
			}
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

