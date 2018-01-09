<?php

namespace TS\Web\Resource;


use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use TS\Web\Resource\Exception\InvalidArgumentException;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class FileResource implements FileResourceInterface
{

	/**
	 * Converts any resource to a local resource.
	 *
	 * @param ResourceInterface $resource
	 * @param string $path
	 * @return FileResource
	 */
	public static function fromResource(ResourceInterface $resource, $path)
	{
		if ($resource instanceof FileResourceInterface) {
			return $resource;
		}
		file_put_contents($path, $resource->getStream());
		$attr = [
			'mimetype' => $resource->getMimetype(),
			'lastmodified' => $resource->getLastModified(),
			'filename' => $resource->getFilename()
		];
		return new FileResource($path, $attr);
	}

	private $path;

	private $filename;

	private $mimetype;

	private $length;

	private $lastModified;

	private $hash;
	
	use OptionsTrait;

	/**
	 *
	 * @param string $path
	 * @param array $options
	 * @throws InvalidArgumentException
	 */
	public function __construct($path, array $options = [])
	{
		if (empty($path)) {
			throw new InvalidArgumentException('Argument "path" is empty.');
		}
		if (! is_string($path)) {
			throw new InvalidArgumentException('Invalid type for argument "path".');
		}
		if (! file_exists($path)) {
			throw new InvalidArgumentException(sprintf('File does not exist: %s.', $path));
		}
		if (is_dir($path)) {
			throw new InvalidArgumentException(sprintf('Path points to a directory: %s.', $path));
		}
		
		$this->path = $path;
		
		$this->filename = $this->takeOption('filename', $options);
		$this->mimetype = $this->takeOption('mimetype', $options);
		$this->content = $this->takeOption('content', $options);
		$this->streamFn = $this->takeOption('stream', $options);
		$this->length = $this->takeOption('length', $options);
		$this->lastModified = $this->takeOption('lastmodified', $options);
		$this->hash = $this->takeOption('hash', $options);
		$this->attributes = $this->takeOption('attributes', $options, []);
		$this->denyRemainingOptions($options);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see FileResourceInterface::open()
	 */
	public function open($mode, resource $context = null)
	{
		if (is_null($context)) {
			return fopen($this->getPath(), $mode, false);
		}
		return fopen($this->getPath(), $mode, false, $context);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see FileResourceInterface::getPath()
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getFilename()
	 */
	public function getFilename()
	{
		if (is_null($this->filename)) {
			$this->filename = pathinfo($this->path, PATHINFO_BASENAME);
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
		if (is_null($this->mimetype)) {
			$ext = pathinfo($this->path, PATHINFO_EXTENSION);
			if ($ext === 'css') {
				return 'text/css';
			}
			if ($ext === 'js') {
				return 'text/javascript';
			}
			$guesser = MimeTypeGuesser::getInstance();
			$this->mimetype = $guesser->guess($this->getPath());
		}
		return $this->mimetype;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLength()
	 */
	public function getLength()
	{
		if (is_null($this->length)) {
			$this->length = filesize($this->path);
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
		if (is_null($this->lastModified)) {
			$this->lastModified = new \DateTime('@' . filemtime($this->path));
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
			$this->hash = sha1_file($this->path);
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
		if (is_null($context)) {
			return fopen($this->path, 'rb', false);
		} else {
			return fopen($this->path, 'rb', false, $context);
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
		return sprintf('[FileResource %s %s %s]', $this->getPath(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
	}

}

