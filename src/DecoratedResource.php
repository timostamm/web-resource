<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class DecoratedResource implements ResourceInterface
{

	private $content = false;

	private $streamFn = false;

	private $filename = false;

	private $mimetype = false;

	private $length = false;

	private $lastModified = false;

	private $hash = false;

	private $resource;
	
	/**
	 *
	 * @param array $attributes
	 */
	public function __construct(ResourceInterface $resource, array $attributes)
	{
		$this->resource = $resource;
		if (array_key_exists('stream', $attributes) && array_key_exists('content', $attributes)) {
			throw new \InvalidArgumentException('Attribute "stream" and "content" are exclusive.');
		}
		$this->acceptAttributes($attributes);
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
		return $this->filename === false ? $this->resource->getFilename() : $this->filename;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getMimetype()
	 */
	public function getMimetype()
	{
		return $this->mimetype === false ? $this->resource->getMimetype() : $this->mimetype;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLength()
	 */
	public function getLength()
	{
		return $this->length === false ? $this->resourcegetLength>getMimetype() : $this->length;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLastModified()
	 */
	public function getLastModified()
	{
		return $this->lastModified === false ? $this->resource->getLastModified() : $this->lastModified;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getHash()
	 */
	public function getHash()
	{
		return $this->hash === false ? $this->resource->getHash() : $this->hash;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getStream()
	 */
	public function getStream($context = null)
	{
		if ($this->content !== false) {
			$stream = fopen('php://memory', 'r+');
			fwrite($stream, $this->content);
			rewind($stream);
			return $stream;
		}
		if ($this->streamFn !== false) {
			$fn = $this->streamFn;
			return $fn($context);
		}
		return $this->resource->getStream($context);
	}

	public function __toString()
	{
		return ResourceUtil::format($this);
	}

}

