<?php

class PathUtil {

	public static function getCleanPathWithTrailingSlash(string &$path)
	{
		if (substr($path, 0, 1) === '/') {
			$path = substr($path, 1);
		}
		if (substr($path, -1, 1) === '/') {
			$path .= substr($path, 0, strlen($path) - 1);
		}
		$path = str_replace(['./', '../'], '', $path);
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
}