<?php

namespace TS\Web\Resource;


use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;


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

	/**
	 *
	 * @param string $path
	 * @param array $attributes
	 * @throws \InvalidArgumentException
	 */
	public function __construct($path, array $attributes = null)
	{
		
		$path = filter_var($path, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		
		if (file_exists($path) == false) {
			throw new \InvalidArgumentException('Input file does not exists: ' . $path);
		}
		
		$this->path = $path;
		
		$this->acceptAttributes($attributes);
	}

	private function acceptAttributes(array & $attributes = null)
	{
		
		if (! $attributes) {
			return;
		}
		
		foreach ($attributes as $key => $val) {
			switch ($key) {
				
				case 'filename':
					if (! is_string($val)) {
						throw new \InvalidArgumentException(sprintf('Expected attribute "%s" to be of type string but got %s.', $key, gettype($val)));
					}
					if (strlen(trim($val)) == 0) {
						throw new \InvalidArgumentException(sprintf('Attribute "%s" is empty.', $key));
					}
					$this->filename = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
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

	public function __toString()
	{
		return sprintf('[FileResource %s %s %s]', $this->getPath(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
	}

}

