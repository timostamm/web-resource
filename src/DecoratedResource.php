<?php

namespace TS\Web\Resource;


use TS\Web\Resource\Exception\InvalidArgumentException;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class DecoratedResource implements DecoratedResourceInterface
{

	private $content = false;

	private $streamFn = false;

	private $filename = false;

	private $mimetype = false;

	private $length = false;

	private $lastModified = false;

	private $hash = false;

	private $attributes = false;

	private $resource;

	use OptionsTrait;

	/**
	 *
	 * @param array $options
	 */
	public function __construct(ResourceInterface $resource, array $options)
	{
		$this->resource = $resource;

		$this->mutuallyExlusiveOptions($options, 'content', 'stream');
		$this->filename = $this->takeOption('filename', $options, false);
		$this->mimetype = $this->takeOption('mimetype', $options, false);
		$this->content = $this->takeOption('content', $options, false);
		$this->streamFn = $this->takeOption('stream', $options, false);
		$this->length = $this->takeOption('length', $options, false);
		$this->lastModified = $this->takeOption('lastmodified', $options, false);
		$this->hash = $this->takeOption('hash', $options, false);
		$this->attributes = $this->takeOption('attributes', $options, false);
		$this->denyRemainingOptions($options);

		if (is_null($this->length) && ! is_null($this->content)) {
			$this->length = strlen($this->content);
		}
	}

	private function acceptOptions(array & $options)
	{

		foreach ($options as $key => $val) {
			switch ($key) {

				case 'content':
					if (! is_string($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be string but got %s.', $key, gettype($val)));
					}
					$this->content = $val;
					break;

				case 'stream':
					if (! is_callable($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be callable but got %s.', $key, gettype($val)));
					}
					$this->streamFn = $val;
					break;

				case 'filename':
					if (! is_string($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
					}
					$this->filename = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					break;

				case 'length':
					if (! is_int($val) && ! is_null($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type int but got %s.', $key, gettype($val)));
					}
					if ($val < 0) {
						throw new InvalidArgumentException(sprintf('Invalid option "%s": %s.', $key, $val));
					}
					$this->length = $val;
					break;

				case 'lastmodified':
					if (! $val instanceof \DateTime) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be a DateTime but got %s.', $key, gettype($val)));
					}
					$this->lastModified = $val;
					break;

				case 'mimetype':
					if (! is_string($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
					}
					$this->mimetype = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					break;

				case 'hash':
					if (! is_string($val)) {
						throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
					}
					$this->hash = $val;
					break;

				default:
					throw new InvalidArgumentException(sprintf('Unknown option "%s".', $key));
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
		return $this->length === false ? $this->resource->getLength() : $this->length;
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
		if (is_string($this->content)) {
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

	/**
	 * (non-PHPdoc)
	 *
	 * {@inheritdoc}
	 * @see ResourceInterface::getAttributes()
	 */
	public function getAttributes(): array
	{
		return $this->attributes === false ? $this->resource->getAttributes() : $this->attributes;
	}


	public function __toString()
	{
		return sprintf('[DecoratedResource %s %s %s]', $this->getFilename(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
	}

	/**
	 * The original, undecorated resource.
	 */
	public function getUndecoratedResource()
	{
		return $this->resource;
	}

}

