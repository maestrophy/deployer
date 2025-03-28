<?php

class PathUtil {

	public static function getCleanPathWithTrailingSlash(string &$path)
	{
		// Simply removing the ./ and ../ path parts, they are not allowed
		$path = str_replace(['/./', '/../'], '/', $path);
		if (substr($path, 0, 2) === './') {
			$path = substr($path, 2);
		}
		if (substr($path, 0, 3) === '../') {
			$path = substr($path, 3);
		}
		if (substr($path, 0, 1) !== '/') {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
		}
		if (substr($path, -1, 1) !== '/') {
			$path .= substr($path, 0, strlen($path) - 1);
		}
		$path .= '/';
	}

	public static function getCleanFullPathWithTrailingSlash(string &$path)
	{
		if (substr($path, 0, 1) !== '/') {
			$path = '/' . $path;
		}
		if (substr($path, -1, 1) === '/') {
			$path .= substr($path, 0, strlen($path) - 1);
		}
		$path = str_replace(['./', '../'], '', $path);
		$path .= '/';
	}

	public static function makeFullPathFromRelative(string $path, bool $withTrailingSlash = true): string
	{
		$root = $_SERVER['DOCUMENT_ROOT'];
		if (substr($path, 0, strlen($root)) !== $root && substr($path, 0, 1) !== '/') {
			$path = $root . (substr($path, 0, 1) === '/' ? '' : '/') . $path;
		}
		$pathParts = array_values(
			array_filter(
				explode('/', substr($path, 1)),
				fn ($part) => !empty($part) && $part !== '.'
			)
		);
		$finalPathParts = [];
		foreach ($pathParts as $part) {
			if ($part === '..') {
				if (count($finalPathParts) === 0) {
					throw new Exception('Trying to get upper, than root in directory structure!');
				}
				array_pop($finalPathParts);
			} else {
				$finalPathParts[] = $part;
			}
		}
		return '/' . join('/', $finalPathParts) . ($withTrailingSlash ? '/' : '');
	}
}