<?php

namespace OomphInc\WASP\FileSystem;

use RuntimeException;

abstract class FileSystemHelper {

	/**
	 * Recursively flatten a nested files array.
	 * @param  array  $files paths to files or an array in the form ['subdir' => [files]]
	 * @param  string [$dir]   starting dir
	 * @return array         flatten array with resolved file names
	 */
	public static function flattenFileArray($files, $dir = null) {
		$out = [];
		$dir = $dir ? static::trailingSlash($dir) : '';
		foreach ($files as $file) {
			if (is_string($file)) {
				$out[] = $dir . $file;
			} else if (is_array($file) && count($file) === 1) {
				reset($file);
				$out = array_merge($out, static::flattenFileArray(current($file), $dir . key($file)));
			}
		}
		return $out;
	}

	/**
	 * Get a relative reference between two paths.
	 * Both paths must either be absolute or relative to the same starting dir.
	 * @param  string $pathFrom  starting path
	 * @param  string $pathTo  ending path
	 * @return string       relative path between the two
	 */
	public static function relativePath($pathFrom, $pathTo) {
		$dirPartsFrom = static::getDirParts($pathFrom);
		$dirPartsTo = static::getDirParts($pathTo);
		// strip off dir parts that the two paths share
		while (reset($dirPartsFrom) === reset($dirPartsTo)) {
			array_shift($dirPartsFrom);
			array_shift($dirPartsTo);
		}
		return DIRECTORY_SEPARATOR . str_repeat('..' . DIRECTORY_SEPARATOR, count($dirPartsFrom))
			. implode(DIRECTORY_SEPARATOR, $dirPartsTo) . (substr($pathTo, -1) === DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');
	}

	/**
	 * Break a path into directory parts, eliminating unnecessary components.
	 * @param  string $path path
	 * @return array       dirs
	 */
	public static function getDirParts($path) {
		return array_diff(explode(DIRECTORY_SEPARATOR, $path), ['', '.']);
	}

	/**
	 * Add a trailing slash to the path, if there isn't one.
	 * @param  string $path path, with or without trailing slash
	 * @return string       path with trailing slash
	 */
	public static function trailingSlash($path) {
		return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Join two or more path components.
	 * @param  string $path... path component
	 * @return string joined paths
	 */
	public static function join() {
		$parts = [];
		foreach (func_get_args() as $i => $part) {
			// trim each component based on its position
			$func = $i === 0 ? 'rtrim' : ($i === func_num_args() - 1 ? 'ltrim' : 'trim');
			$parts[] = $func($part, DIRECTORY_SEPARATOR);
		}
		return implode(DIRECTORY_SEPARATOR, $parts);
	}

}
