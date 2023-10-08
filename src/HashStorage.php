<?php

namespace TS\Web\Resource;


use Exception;
use Generator;
use Symfony\Component\Mime\MimeTypes;
use TS\Web\Resource\Exception\IOException;
use TS\Web\Resource\Exception\OutOfBoundsException;
use TS\Web\Resource\Exception\StorageLogicException;


class HashStorage
{

    /**
     * @var string
     */
    private $rootDir;


    /**
     * Was the existence of the storage directory checked?
     * @var bool
     */
    private $ensuredRootDir = false;


    public function __construct(string $dir, bool $ensureStorageDirectoryExistsImmediately = false)
    {
        $this->rootDir = $dir;
        if ($ensureStorageDirectoryExistsImmediately) {
            $this->ensureRootDirExists();
        }
    }


    public function put(ResourceInterface $res): void
    {
        $this->ensureRootDirExists();

        $hash = $res->getHash();

        if ($this->has($hash)) {
            $msg = sprintf('Resource with the same hash "%s" is already present.', $hash);
            throw new IOException($msg);
        }

        $dir = $this->makeResourceDirPath($hash);
        $dir_tmp = $dir . '.tmp';

        if (file_exists($dir_tmp)) {
            $this->deleteResourceDir($dir_tmp);
        }

        $ok = @mkdir($dir_tmp, 0777, true);
        if ($ok === false) {
            $msg = sprintf('Failed to store resource. Unable to create directory "%s".', $dir_tmp);
            throw new IOException($msg);
        }

        try {

            $mime = $res->getMimetype();
            $content_name = $this->makeContentFilename($mime);
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
            if (!$ok) {
                $msg = sprintf('Failed to write meta file "%s".', $meta_path);
                throw new IOException($msg);
            }

            file_put_contents($cont_path, $res->getStream());
            if (!$ok) {
                $msg = sprintf('Failed to write content file "%s".', $cont_path);
                throw new IOException($msg);
            }

            $ok = @rename($dir_tmp, $dir);
            if ($ok === false) {
                $msg = sprintf('Failed to commit storage directory "%s".', $dir_tmp);
                throw new Exception($msg);
            }

        } catch (Exception $exception) {
            try {
                if (file_exists($dir_tmp)) {
                    $this->deleteResourceDir($dir_tmp, $exception);
                }
            } catch (Exception $cleanup) {
                throw new IOException('Failed to store resource.', 0, $cleanup);
            }
            throw new IOException('Failed to store resource.', 0, $exception);
        }
    }


    public function remove(string $hash): void
    {
        $this->ensureRootDirExists();

        if (!$this->has($hash)) {
            $msg = sprintf('The hash "%s" is not present.', $hash);
            throw new OutOfBoundsException($msg);
        }
        $resourceDir = $this->makeResourceDirPath($hash);
        $this->deleteResourceDir($resourceDir);
    }


    public function get(string $hash): ResourceInterface
    {
        $this->ensureRootDirExists();

        if (!$this->has($hash)) {
            throw new OutOfBoundsException();
        }

        $meta_path = $this->makeResourceDirPath($hash, 'meta.bin');
        if (!is_file($meta_path)) {
            $msg = sprintf('Missing meta file "%s".', $meta_path);
            throw new StorageLogicException($msg);
        }

        $meta = @file_get_contents($meta_path);
        if ($meta === false) {
            $msg = sprintf('Failed to read meta file "%s".', $meta_path);
            throw new IOException($msg);
        }

        list ($filename, $lastmodified, $length, $attributes, $mimetype, $content_name) = unserialize($meta);
        $content_path = $this->makeResourceDirPath($hash, $content_name);

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
        $this->ensureRootDirExists();
        $resourceDir = $this->makeResourceDirPath($hash);
        return is_dir($resourceDir);
    }


    public function find(string $hash): ?ResourceInterface
    {
        $this->ensureRootDirExists();
        return $this->has($hash) ? $this->get($hash) : null;
    }


    public function listHashes(): Generator
    {
        $this->ensureRootDirExists();
        foreach (scandir($this->rootDir, SCANDIR_SORT_NONE) as $a) {
            if ($a === '..' || $a === '.') {
                continue;
            }
            foreach (scandir($this->rootDir . '/' . $a, SCANDIR_SORT_NONE) as $b) {
                if ($b === '..' || $b === '.') {
                    continue;
                }
                yield $b;
            }
        }
    }


    /**
     * Check that the storage directory exists. Try to create the
     * directory if it does not exist.
     */
    protected function ensureRootDirExists(): void
    {
        if ($this->ensuredRootDir) {
            return;
        }
        if (!file_exists($this->rootDir)) {
            if (!@mkdir($this->rootDir)) {
                $msg = sprintf('The storage directory "%s" could not be created.', $this->rootDir);
                throw new StorageLogicException($msg);
            }
        }
        $this->ensuredRootDir = true;
    }


    protected function makeContentFilename(string $mimetype): string
    {
        $name = 'content';
        $mimeTypes = new MimeTypes();
        $ext = $mimeTypes->getExtensions($mimetype)[0] ?? null;
        if (!empty($ext)) {
            $name .= '.' . $ext;
        }
        return $name;
    }


    protected function makeResourceDirPath(string $hash, string $filename = null): string
    {
        $resourceDir = "{$this->rootDir}/{$hash[0]}{$hash[1]}/{$hash}";
        if (is_string($filename)) {
            return $resourceDir . '/' . $filename;
        }
        return $resourceDir;
    }


    protected function deleteResourceDir(string $resourceDir, Exception $reason = null): void
    {
        foreach (scandir($resourceDir, SCANDIR_SORT_NONE) as $filename) {
            if ($filename === 'meta.bin' || strpos($filename, 'content') === 0) {
                $ok = @unlink($resourceDir . '/' . $filename);
                if (!$ok) {
                    $msg = sprintf('Failed to delete file "%s" in resource directory "%s".', $filename, $resourceDir);
                    throw new IOException($msg, 0, $reason);
                }
            }
        }

        $ok = @rmdir($resourceDir);
        if ($ok === false) {
            $msg = sprintf('Failed to delete resource directory "%s".', $resourceDir);
            throw new IOException($msg, 0, $reason);
        }
    }

}

