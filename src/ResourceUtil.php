<?php

namespace TS\Web\Resource;


use TS\Web\Resource\Exception\IOException;
use TS\Web\Resource\Exception\InvalidArgumentException;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class ResourceUtil
{

	public static function makeDataUri(ResourceInterface $resource, $maxSize = 102400)
	{
		if ($resource->getLength() > $maxSize) {
			return false;
		}
		$h = $resource->getStream();
		$c = stream_get_contents($h, $maxSize);
		fclose($h);
		return sprintf('data:%s;base64,%s', $resource->getMimetype(), base64_encode($c));
	}

	public static function formatSize($bytes, $precision = 2)
	{
		if (! is_int($bytes) || ! is_finite($bytes)) {
			return '?';
		}
		$units = [
			'B',
			'KB',
			'MB',
			'GB',
			'TB'
		];
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));
		return round($bytes, $precision) . $units[$pow];
	}

	public static function createTempDir()
	{
		$returnCode = 0;
		$outputLines = [];
		$command = 'mktemp 2>&1 -d';
		$lastLine = exec($command, $outputLines, $returnCode);
		if ($returnCode !== 0) {
			$msg = sprintf('Failed to create temp dir using mktemp. Command "%s" exited with code %s and output "%s".', $command, $returnCode, implode("\n", $outputLines));
			throw new IOException($msg);
		}
		if (! is_dir($lastLine)) {
			throw new IOException(sprintf('Failed to create temp dir "%s".', $lastLine));
		}
		return $lastLine . DIRECTORY_SEPARATOR;
	}

	public static function createTempFile($filename)
	{
		if (empty($filename)) {
			throw new InvalidArgumentException('Invalid filename "' . $filename . '"');
		}
		$file = trim($filename);
		if (class_exists('\\Normalizer')) {
			$file = (new \Normalizer())->normalize($file);
		}
		$file = filter_var($file, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$file = str_replace(['..', ':', '/', '\\'], '', $file);
		if (empty($filename)) {
			throw new InvalidArgumentException('Invalid filename "' . $filename . '"');
		}
		$returnCode = 0;
		$outputLines = [];
		$command = 'mktemp 2>&1 -d';
		$lastLine = exec($command, $outputLines, $returnCode);
		if ($returnCode !== 0) {
			$msg = sprintf('Failed to create temp dir using mktemp. Command "%s" exited with code %s and output "%s".', $command, $returnCode, implode("\n", $outputLines));
			throw new IOException($msg);
		}
		if (! is_dir($lastLine)) {
			throw new IOException(sprintf('Failed to create temp dir "%s".', $lastLine));
		}
		return $lastLine . DIRECTORY_SEPARATOR . $file;
	}

}

