<?php

namespace OomphInc\WASP\FileSystem;

use RuntimeException;

class FileSystem implements FileSystemInterface {

	use DirStackTrait;

	/**
	 * @param string [$cwd] optional starting current working directory
	 */
	public function __construct($cwd = null) {
		$this->setCwd($cwd);
	}

	/**
	 * @inheritDoc
	 */
	public function resolvePath($path) {
		// default path if null provided
		if ($path === null) {
			return getcwd();
		}

		// is the path relative?
		if (!preg_match(sprintf('#^%1$s|[a-z]+:%1$s{1,2}#i', preg_quote(DIRECTORY_SEPARATOR)), $path)) {
			$path = $this->getCwd() . DIRECTORY_SEPARATOR . $path;
		}

		return is_readable($path) ? realpath($path) : $path;
	}

	/**
	 * @inheritDoc
	 */
	public function getFiles($pattern, $relative = true) {
		$cwd = $this->getCwd();
		$files = [];
		foreach (glob($cwd . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE) as $file) {
			// strip off the files root path
			$files[] = $relative ? preg_replace('#^' . preg_quote(rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, '#') . '#', '', $file) : $file;
		}
		return $files;
	}

	/**
	 * @inheritDoc
	 */
	public function fileExists($path) {
		return is_readable($this->resolvePath($path));
	}

	/**
	 * @inheritDoc
	 */
	public function readFile($path) {
		$file = file_get_contents($this->resolvePath($path));
		if ($file === false) {
			throw new RuntimeException("Could not read file '$path'");
		}
		return $file;
	}

	/**
	 * @inheritDoc
	 */
	public function writeFile($path, $contents) {
		if (file_put_contents($this->resolvePath($path), $contents) === false) {
			throw new RuntimeException("Could not write to file '$path'");
		}
	}

	/**
	 * @inheritDoc
	 */
	public function deleteFile($path) {
		// bail early if it doesn't exit
		if (!$this->fileExists($path)) {
			return;
		}

		// store original path to use in error message, if applicable
		$origPath = $path;
		$path = $this->resolvePath($path);

		// is this a dir?
		if (is_dir($path)) {
			try {
				array_walk(array_diff(scandir($path), ['..', '.']), [$this, 'deleteFile']);
			} catch (RuntimeException $e) {}

			// failure?
			if (isset($e) || !rmdir($path)) {
				throw new RuntimeException("Could not delete dir '$origPath'");
			}
		} else if (!unlink($path)) {
			throw new RuntimeException("Could not delete file '$origPath'");
		}
	}

	/**
	 * @inheritDoc
	 */
	public function renameFile($oldPath, $newPath) {
		if ($this->fileExists($oldPath)) {
			if (!rename($this->resolvePath($oldPath), $this->resolvePath($newPath))) {
				throw new RuntimeException("Could not rename file '$oldPath'");
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function createDir($path) {
		if (!mkdir($this->resolvePath($path), 0777, true)) {
			throw new RuntimeException("Could not create directory '$path'");
		}
	}

}
