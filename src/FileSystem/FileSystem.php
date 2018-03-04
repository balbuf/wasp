<?php

namespace OomphInc\WASP\FileSystem;

use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;


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
			$path = FileSystemHelper::join($this->getCwd(), $path);
		}

		return is_readable($path) ? realpath($path) : $path;
	}

	/**
	 * @inheritDoc
	 */
	public function getFiles($pattern = null, $flags = FileSystemInterface::RECURSIVE | FileSystemInterface::RELATIVE) {
		$cwd = FileSystemHelper::trailingSlash($this->getCwd());
		$cwdLen = strlen($cwd);
		$isRelative = (bool) ($flags & FileSystemInterface::RELATIVE);
		$files = [];

		if ($flags & FileSystemInterface::RECURSIVE) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cwd));
		} else {
			$iterator = new DirectoryIterator($cwd);
		}

		foreach ($iterator as $file) {
			// skip '.' and '..'
			if ($iterator->isDot()) {
				continue;
			}

			// do we want only files or dirs? check based on the realpath to include symlinks that point to the respective types
			if ($flags & FileSystemInterface::ONLY_FILES && !is_file($file->getRealPath())) {
				continue;
			}
			if ($flags & FileSystemInterface::ONLY_DIRS && !is_dir($file->getRealPath())) {
				continue;
			}

			// convert the file object to just the filename
			$file = (string) $file;

			if ($pattern !== null && !preg_match($pattern, $file)) {
				continue;
			}

			// add file, adding or removing the cwd path as necessary
			if (substr($file, 0, $cwdLen) === $cwd) {
				if ($isRelative) {
					// strip off leading path
					$file = substr($file, $cwdLen);
				}
			} else {
				if (!$isRelative) {
					// add leading path
					$file = FileSystemHelper::join($cwd, $file);
				}
			}
			$files[] = $file;
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
		$file = @file_get_contents($this->resolvePath($path));
		if ($file === false) {
			throw new RuntimeException("Could not read file '$path'");
		}
		return $file;
	}

	/**
	 * @inheritDoc
	 */
	public function writeFile($path, $contents) {
		if (@file_put_contents($this->resolvePath($path), $contents) === false) {
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
		} else if (!@unlink($path)) {
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
