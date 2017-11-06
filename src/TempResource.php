<?php

namespace TS\Web\Resource;


use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class TempResource implements LocalResourceInterface, TemporaryResourceInterface
{

	public static function fromResource(ResourceInterface $resource)
	{
		if ($resource instanceof TempResource) {
			return $resource;
		}
		$temp = new TempResource($resource->getFilename(), $resource->getMimetype(), $resource->getLastModified());
		file_put_contents($temp->getPath(), $resource->getStream());
		return $temp;
	}

	private $path;

	private $filename;

	private $mimetype;

	private $hashTimestamp = 0;

	private $hash;

	private $disposed = false;

	/**
	 *
	 * @param string $path
	 * @param string $mimetype
	 */
	public function __construct($filename = null, $mimetype = null, \DateTime $lastmodified = null)
	{
		if ($filename == null || trim($filename) === '') {
			$this->filename = 'temp';
			if (isset($attributes['mimetype'])) {
				$this->filename .= '.' . ExtensionGuesser::getInstance()->guess($attributes['mimetype']);
			}
		} else {
			$filename = filter_var($filename, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
			if (class_exists('\\Normalizer')) {
				$filename = (new \Normalizer())->normalize($filename);
			}
			$filename = str_replace('..', '', $filename);
			$filename = str_replace(':', '', $filename);
			$filename = str_replace('/', '', $filename);
			$filename = str_replace('\\', '', $filename);
			$this->filename = $filename;
		}
		
		$this->mimetype = $mimetype;
		$this->lastmodified = $lastmodified;
		
		$this->path = static::createTempFile($this->filename);
		
		touch($this->path);
		
		// keep a reference so that the file will stay until php ends
		static::$instances[] = $this;
	}

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
	 * @see LocalResourceInterface::getPath()
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
			$guesser = MimeTypeGuesser::getInstance();
			$this->mimetype = $guesser->guess($this->path);
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
		if ($this->lastmodified) {
			return $this->lastmodified;
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

	public function __toString()
	{
		return sprintf('[TempResource %s %s %s]', $this->getFilename(), $this->getMimetype(), ResourceUtil::formatSize($this->getLength()));
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

	private static function createTempFile($filename)
	{
		$returnCode = 0;
		$outputLines = [];
		$command = 'mktemp 2>&1 -d';
		$lastLine = exec($command, $outputLines, $returnCode);
		if ($returnCode !== 0) {
			$msg = sprintf('Failed to create temp dir using mktemp. Command "%s" exited with code %s and output "%s".', $command, $returnCode, implode("\n", $outputLines));
			throw new \LogicException($msg);
		}
		if (! is_dir($lastLine)) {
			throw new \LogicException(sprintf('Failed to create temp dir "%s".', $lastLine));
		}
		return $lastLine . DIRECTORY_SEPARATOR . $filename;
	}

	private static function createTempDir()
	{
		$returnCode;
		$outputLines = [];
		$command = 'mktemp 2>&1 -d';
		$lastLine = exec($command, $outputLines, $returnCode);
		if ($returnCode !== 0) {
			$msg = sprintf('Failed to create temp dir using mktemp. Command "%s" exited with code %s and output "%s".', $command, $returnCode, implode("\n", $outputLines));
			throw new \LogicException($msg);
		}
		if (! is_dir($lastLine)) {
			throw new \LogicException(sprintf('Failed to create temp dir "%s".', $lastLine));
		}
		return $lastLine;
	}

}

