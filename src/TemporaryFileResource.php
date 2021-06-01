<?php

namespace TS\Web\Resource;


use Symfony\Component\Mime\MimeTypes;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class TemporaryFileResource implements FileResourceInterface, TemporaryResourceInterface
{

	public static function fromResource(ResourceInterface $resource)
	{
		if ($resource instanceof TemporaryFileResource) {
			return $resource;
		}
		$temp = new TemporaryFileResource($resource->getFilename(), $resource->getMimetype(), $resource->getLastModified());
		file_put_contents($temp->getPath(), $resource->getStream());
		return $temp;
	}

	private $path;

	private $filename;

	private $mimetype;

	private $lastModified;

	private $hashTimestamp = 0;

	private $hash;

	private $attributes;
	
	private $disposed = false;
	
	use OptionsTrait;

    /**
     * TemporaryFileResource constructor.
     * @param null $filename
     * @param null $mimetype
     * @param \DateTimeInterface|null $lastmodified
     * @param array|null $attributes
     */
	public function __construct($filename = null, $mimetype = null, \DateTimeInterface $lastmodified = null, array $attributes = null)
	{
		$this->filename = $this->validateOptional('filename', $filename, 'temp');
		$this->mimetype = $this->validateOptional('mimetype', $mimetype);
		$this->lastModified = $this->validateOptional('lastmodified', $lastmodified);
		$this->attributes = $this->validateOptional('attributes', $attributes, []);
		
		if (is_string($this->mimetype) && $this->filename === 'temp') {
            $mimeTypes = new MimeTypes();
			$ext = $mimeTypes->getExtensions($this->mimetype)[0] ?? null;
			if (! empty($ext)) {
				$this->filename .= '.' . $ext;
			}
		}
		
		$this->path = ResourceUtil::createTempFile($this->filename);
		
		touch($this->path);
		
		// keep a reference so that the file will stay until php ends
		static::$instances[] = $this;
	}

	public function open($mode, $context = null)
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
            $mimeTypes = new MimeTypes();
			$this->mimetype = $mimeTypes->guessMimeType($this->path);
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
		return filesize($this->path);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getLastModified()
	 */
	public function getLastModified()
	{
		if ($this->lastModified) {
			return $this->lastModified;
		}
		return new \DateTime('@' . filemtime($this->path));
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ResourceInterface::getHash()
	 */
	public function getHash()
	{
		if (is_null($this->hash) || filemtime($this->path) > $this->hashTimestamp) {
			$this->hash = sha1_file($this->path);
			$this->hashTimestamp = filemtime($this->path);
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
		return sprintf('[TemporaryFileResource %s %s %s]', $this->getFilename(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
	}

	public function dispose()
	{
		if (file_exists($this->path)) {
			unlink($this->path);
		}
		rmdir(dirname($this->path));
		$i = array_search($this, static::$instances);
		if ($i !== false) {
			unset(static::$instances[$i]);
		}
		$this->disposed = true;
	}

	public function __destruct()
	{
		if (! $this->disposed) {
			$this->dispose();
		}
	}

	private static $instances = [];

}

