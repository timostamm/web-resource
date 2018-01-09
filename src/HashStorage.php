<?php

namespace TS\Web\Resource;


use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use TS\Web\Resource\Exception\IOException;
use TS\Web\Resource\Exception\OutOfBoundsException;
use TS\Web\Resource\Exception\StorageLogicException;
use Generator;


class HashStorage
{

	private $dir;

	public function __construct(string $dir)
	{
		if (! file_exists($dir)) {
			if (! @mkdir($dir)) {
				$msg = sprintf('The storage directory "%s" could not be created.', $dir);
				throw new StorageLogicException($msg);
			}
		}
		$this->dir = $dir;
	}

	public function put(ResourceInterface $res): void
	{
		$hash = $res->getHash();
		
		$dir = $this->mapDir($hash);
		$dir_tmp = $dir . '.tmp';
		
		if (file_exists($dir)) {
			$msg = sprintf('Resource with the same hash "%s" is already present.', $hash);
			throw new IOException($msg);
		}
		if (file_exists($dir_tmp)) {
			$this->deleteEntryDirectory($dir_tmp);
		}
		
		$ok = @mkdir($dir_tmp, 0777, true);
		if ($ok === false) {
			$msg = sprintf('Failed to store resource. Unable to create directory "%s".', $dir_tmp);
			throw new IOException($msg);
		}
		
		try {
			
			$mime = $res->getMimetype();
			$content_name = $this->contentName($hash, $mime);
			$meta = [
				$res->getFilename(),
				$res->getLastModified(),
				$res->getLength(),
				$res->getAttributes(), 
				$mime,
				$content_name
			];
			
			$meta_path = $dir_tmp . '/meta.bin';
			$cont_path = $dir_tmp . '/' . $content_name;
			
			$ok = file_put_contents($meta_path, serialize($meta));
			if (! $ok) {
				$msg = sprintf('Failed to write meta file "%s".', $meta_path);
				throw new IOException($msg);
			}
			
			file_put_contents($cont_path, $res->getStream());
			if (! $ok) {
				$msg = sprintf('Failed to write content file "%s".', $cont_path);
				throw new IOException($msg);
			}
			
			$ok = @rename($dir_tmp, $dir);
			if ($ok === false) {
				$msg = sprintf('Failed to commit storage directory "%s".', $dir_tmp);
				throw new \Exception($msg);
			}
		
		} catch (\Exception $creation) {
			try {
				if (file_exists($dir_tmp)) {
					$this->deleteEntryDirectory($dir_tmp, $creation);
				}
			} catch (\Exception $cleanup) {
				throw new IOException('Failed to store resource.', null, $cleanup);
			}
			throw new IOException('Failed to store resource.', null, $creation);
		}
	}

	public function remove(string $hash): void
	{
		if (! $this->has($hash)) {
			$msg = sprintf('The hash "%s" is not present.', $hash);
			throw new OutOfBoundsException($msg);
		}
		$file = $this->dir . '/' . $hash;
		unlink($file);
		unset($this->entries[$hash]);
	}

	public function get(string $hash): ResourceInterface
	{
		$dir = $this->mapDir($hash);
		$meta_path = $dir . '/meta.bin';
		if (! file_exists($meta_path)) {
			throw new OutOfBoundsException();
		}
		if (! is_file($meta_path)) {
			$msg = sprintf('Missing meta file "%s".', $meta_path);
			throw new StorageLogicException($msg);
		}
		$meta = @file_get_contents($meta_path);
		if ($meta === false) {
			$msg = sprintf('Failed to read meta file "%s".', $meta_path);
			throw new IOException($msg);
		}
		list ($filename, $lastmodified, $length, $attributes, $mimetype, $content_name) = unserialize($meta);
		$content_path = $dir . '/' . $content_name;
		
		$resource = new FileResource($content_path, [
			'filename' => $filename,
			'lastmodified' => $lastmodified,
			'length' => $length,
			'mimetype' => $mimetype, 
			'attributes' => $attributes
		]);
		return $resource;
	}

	public function has(string $hash): bool
	{
		return file_exists($this->mapDir($hash) . '/meta.bin');
	}

	public function find(string $hash): ?ResourceInterface
	{
		return $this->has($hash) ? $this->get($hash) : null;
	}

	public function listHashes(): Generator
	{
		foreach (scandir($this->dir, SCANDIR_SORT_NONE) as $a) {
			if ($a === '..' || $a === '.') {
				continue;
			}
			foreach (scandir($this->dir . '/' . $a, SCANDIR_SORT_NONE) as $b) {
				if ($b === '..' || $b === '.') {
					continue;
				}
				yield $b;
			}
		}
	}

	protected function contentName(string $hash, $mimetype): string
	{
		$name = 'content';
		$ext = ExtensionGuesser::getInstance()->guess($mimetype);
		if (! empty($ext)) {
			$name .= '.' . $ext;
		}
		return $name;
	}

	protected function mapDir(string $hash): string
	{
		return $this->dir . '/' . $hash[0] . $hash[1] . '/' . $hash;
	}

	protected function deleteEntryDirectory(string $dir, \Exception $reason = null): void
	{
		foreach (scandir($dir, SCANDIR_SORT_NONE) as $f) {
			if ($f === 'meta.bin' || strpos($f, 'file.') === 0) {
				
				$ok = @unlink($dir . '/' . $f);
				if (! $ok) {
					$msg = sprintf('Failed to cleanup entry file "%s" in "%s".', $f, $dir);
					throw new IOException($msg, null, $reason);
				}
			
			}
		}
		
		$ok = @rmdir($dir);
		if ($ok === false) {
			$msg = sprintf('Failed to cleanup entry directory "%s".', $dir);
			throw new IOException($msg, null, $reason);
		}
	}

}

