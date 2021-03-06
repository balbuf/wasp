<?php

namespace OomphInc\WASP\FileSystem;

interface FileSystemInterface {

	// flags for getFiles()
	const RECURSIVE = 1;
	const RELATIVE = 2;
	const ONLY_FILES = 4;
	const ONLY_DIRS = 8;

	/**
	 * Set the current working directory, which relative paths are based off of.
	 * @param string $cwd path
	 */
	public function setCwd($cwd);

	/**
	 * Get the current working directory.
	 * @return string path
	 */
	public function getCwd();

	/**
	 * Push a new cwd onto the stack and switch to it.
	 * @param  string $cwd path
	 */
	public function pushd($cwd);

	/**
	 * Pop cwd off stack and switch to next on the stack.
	 */
	public function popd();

	/**
	 * Resolve a path relative to cwd, if applicable.
	 * @param  string $path absolute or relative path
	 * @return string       resolved real path
	 */
	public function resolvePath($path);

	/**
	 * Determine whether a file/dir exists and is readable.
	 * @param  string $path path
	 * @return bool       determination
	 */
	public function fileExists($path);

	/**
	 * Get a list of files matching the pattern, relative to the current working directory.
	 * @param  string $pattern  file regex pattern or null to return all
	 * @param  int   $flags  optional flags that control search
	 * @return array           matching paths
	 */
	public function getFiles($pattern, $flags);

	/**
	 * Read the contents of the file at the given path.
	 * @param  string $path file path
	 * @return string       contents of file
	 */
	public function readFile($path);

	/**
	 * Write to a file.
	 * @param  string $path     file path
	 * @param  string $contents file contents
	 */
	public function writeFile($path, $contents);

	/**
	 * Delete a file or dir (recursively), if it exists.
	 * @param  string $path  file path
	 */
	public function deleteFile($path);

	/**
	 * Rename a file, if it exists.
	 * @param  string $oldPath existing path
	 * @param  string $newPath new path
	 */
	public function renameFile($oldPath, $newPath);

	/**
	 * Make a new directory, including any intermediate new directories.
	 * @param  string $path path
	 */
	public function createDir($path);

}
