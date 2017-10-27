<?php

namespace OomphInc\WASP;

use RuntimeException;

class FileSystemHelper {

	protected $application;
	protected $rootDir;

	public function __construct($application, $rootDir) {
		$this->application = $application;
		$this->setRootDir($rootDir);
	}

	public function setRootDir($rootDir) {
		$this->rootDir = realpath($rootDir);
	}

	public function getRootDir() {
		return $this->rootDir;
	}

	/**
	 * Glob within the file root.
	 * @param  string $pattern glob pattern, relative to file root
	 * @return array          file paths, absolute or relative to file root
	 */
	public function getFiles($pattern, $relative = true) {
		$root = $this->getRootDir();
		$files = [];
		foreach(glob($root . '/' . $pattern, GLOB_BRACE) as $file) {
			// strip off the files root path
			$files[] = $relative ? preg_replace('#^' . preg_quote($root, '#') . '#', '', $file) : $file;
		}
		return $files;
	}

	/**
	 * Get the contents of the file at the specific path.
	 * @param  string $path path to file relative to root dir
	 * @return string       contents of file
	 */
	public function readFile($path, $relative = true) {
		if ($relative) {
			$path = $this->getRootDir() . '/' . $path;
		}
		$file = file_get_contents($path);
		if ($file === false) {
			throw new RuntimeException("Could not read file '$path'");
		}
		return $file;
	}

	public function writeFile($path, $relative = true, $contents) {
		if ($relative) {
			$path = $this->getRootDir() . '/' . $path;
		}
		return file_put_contents($path, $contents);
	}

	/**
	 * Recursively flatten a nested files array.
	 * @param  string $dir   starting dir
	 * @param  array  $files paths to files or an array in the form ['subdir' => [files]]
	 * @return array         flatten array with resolved file names
	 */
	public static function flattenFileArray($dir, $files) {
		$out = [];
		foreach ($files as $file) {
			if (is_string($file)) {
				$out[] = $dir . '/' . $file;
			} else if (is_array($file) && count($file) === 1) {
				reset($file);
				$out = array_merge($out, static::flattenFileArray($dir . '/' . key($file), current($file)));
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
		return '/' . str_repeat('../', count($dirPartsFrom)) . implode('/', $dirPartsTo) . (substr($pathTo, -1) === '/' ? '/' : '');
	}

	/**
	 * Break a path into directory parts, eliminating unnecessary components.
	 * @param  string $path path
	 * @return array       dirs
	 */
	public static function getDirParts($path) {
		return array_diff(explode('/', $path), ['', '.']);
	}

}
