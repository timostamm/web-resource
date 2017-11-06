<?php

namespace TS\Web\Resource;


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
			throw new \LogicException($msg);
		}
		if (! is_dir($lastLine)) {
			throw new \LogicException(sprintf('Failed to create temp dir "%s".', $lastLine));
		}
		return $lastLine . DIRECTORY_SEPARATOR;
	}

}

