<?php

namespace TS\Web\Resource;


use TS\Web\Resource\Exception\InvalidArgumentException;


trait OptionsTrait {

	private function requireOptions(array $options, array $keys)
	{
		$present = array_intersect(array_keys($options), $keys);
		$missing = array_diff($keys, $present);
		if (! empty($missing)) {
			$msg = sprintf('Missing options "%s"', join('", "', $missing));
			throw new InvalidArgumentException($msg);
		}
	}

	private function requireEitherOption(array $options, $key1, $key2)
	{
		$e1 = array_key_exists($key1, $options);
		$e2 = array_key_exists($key2, $options);
		if (! $e1 && ! $e2) {
			$msg = sprintf('Missing option "%s" or "%s".', $key1, $key2);
			throw new InvalidArgumentException($msg);
		}
	}

	private function mutuallyExlusiveOptions(array $options, $key1, $key2)
	{
		$e1 = array_key_exists($key1, $options);
		$e2 = array_key_exists($key2, $options);
		if ($e1 && $e2) {
			$msg = sprintf('The options "%s" and "%s" are mutually exclusive.', $key1, $key2);
			throw new InvalidArgumentException($msg);
		}
	}

	private function takeOption($key, array & $options, $defaultValue = null)
	{
		if (! array_key_exists($key, $options)) {
			return $defaultValue;
		}
		$val = $this->validateOption($key, $options[$key]);
		unset($options[$key]);
		return $val;
	}

	private function denyRemainingOptions(array $options)
	{
		if (! empty($options)) {
			$msg = sprintf('Unknown options "%s"', join('", "', array_keys($options)));
			throw new InvalidArgumentException($msg);
		}
	}

	private function validateOptional($key, $val, $defaultValue = null)
	{
		if (is_null($val)) {
			return $defaultValue;
		}
		return $this->validateOption($key, $val);
	}

	private function validateOption($key, $val)
	{
		switch ($key) {

			case 'content':
				if (! is_string($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be string but got %s.', $key, gettype($val)));
				}
				break;

			case 'stream':
				if (! is_callable($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be callable but got %s.', $key, gettype($val)));
				}
				break;

			case 'filename':
				if (! is_string($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
				}
				if (strlen(trim($val)) == 0) {
					throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
				}
				if (class_exists('\\Normalizer')) {
					$val = (new \Normalizer())->normalize($val);
				}
				$val = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
				$val = str_replace(['..', ':', '/', '\\'], '', $val);
				break;

			case 'length':
				if (! is_int($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type int but got %s.', $key, gettype($val)));
				}
				if ($val < 0) {
					throw new InvalidArgumentException(sprintf('Invalid option "%s": %s.', $key, $val));
				}
				break;

			case 'lastmodified':
				if (! $val instanceof \DateTimeInterface) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be a DateTimeInterface but got %s.', $key, is_object($val) ? get_class($val) : gettype($val)));
				}
				break;

			case 'mimetype':
				if (! is_string($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
				}
				if (strlen(trim($val)) == 0) {
					throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
				}
				$val = filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
				break;

			case 'hash':
				if (! is_string($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type string but got %s.', $key, gettype($val)));
				}
				if (strlen(trim($val)) == 0) {
					throw new InvalidArgumentException(sprintf('Option "%s" is empty.', $key));
				}
				break;

			case 'timeout':
				if (! is_int($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be of type integer but got %s.', $key, gettype($val)));
				}
				break;

			case 'attributes':
				if (! is_array($val)) {
					throw new InvalidArgumentException(sprintf('Expected option "%s" to be array but got %s.', $key, gettype($val)));
				}
				break;

			default:
				throw new InvalidArgumentException(sprintf('Unknown option "%s".', $key));
		}
		return $val;
	}

}

