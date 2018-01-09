<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class Resource implements ResourceInterface
{

	public static function createTemp($filename = null, $mimetype = null, \DateTimeInterface $lastmodified = null)
	{
		return new TemporaryFileResource($filename, $mimetype, $lastmodified);
	}

	public static function fromFile($path, array $options = [])
	{
		return new FileResource($path, $options);
	}

	public static function fromUrl($url, array $options = [])
	{
		return new UrlResource($url, $options);
	}

	public static function decorate(ResourceInterface $resource, array $options)
	{
		return new DecoratedResource($resource, $options);
	}

	public static function addAttributes(ResourceInterface $resource, array $attributes)
	{
		return new DecoratedResource($resource, [
			'attributes' => array_replace($resource->getAttributes(), $attributes)
		]);
	}

	private $content;

	private $streamFn;

	private $filename;

	private $mimetype;

	private $length;

	private $lastModified;

	private $hash;

	private $attributes;
	
	use OptionsTrait;

	/**
	 *
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$this->requireOptions($options, [
			'filename',
			'mimetype'
		]);
		$this->requireEitherOption($options, 'content', 'stream');
		$this->mutuallyExlusiveOptions($options, 'content', 'stream');
		$this->requireEitherOption($options, 'length', 'content');
		$this->filename = $this->takeOption('filename', $options);
		$this->mimetype = $this->takeOption('mimetype', $options);
		$this->content = $this->takeOption('content', $options);
		$this->streamFn = $this->takeOption('stream', $options);
		$this->length = $this->takeOption('length', $options);
		$this->lastModified = $this->takeOption('lastmodified', $options);
		$this->hash = $this->takeOption('hash', $options);
		$this->attributes = $this->takeOption('attributes', $options, []);
		$this->denyRemainingOptions($options);
		
		if (is_null($this->length) && ! is_null($this->content)) {
			$this->length = strlen($this->content);
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
		return sprintf('[Resource %s %s %s]', $this->getFilename(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
	}

}

