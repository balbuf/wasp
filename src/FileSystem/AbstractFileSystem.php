<?php

namespace OomphInc\WASP\FileSystem;

abstract class AbstractFileSystem implements FileSystemInterface {

	protected $dirStack = [];
	protected $cwd;

	/**
	 * @inheritDoc
	 */
	public function setCwd($cwd) {
		$this->cwd = $this->resolvePath($cwd);
	}

	/**
	 * @inheritDoc
	 */
	public function getCwd() {
		return $this->cwd;
	}

	/**
	 * @inheritDoc
	 */
	public function pushd($cwd) {
		$this->dirStack[] = $this->getCwd();
		$this->setCwd($cwd);
	}

	/**
	 * @inheritDoc
	 */
	public function popd() {
		$this->setCwd(array_pop($this->dirStack));
	}

	/**
	 * @inheritDoc
	 */
	abstract public function resolvePath($path);

	/**
	 * @inheritDoc
	 */
	abstract public function fileExists($path);

	/**
	 * @inheritDoc
	 */
	abstract public function getFiles($pattern, $relative = true);

	/**
	 * @inheritDoc
	 */
	abstract public function readFile($path);

	/**
	 * @inheritDoc
	 */
	abstract public function writeFile($path, $contents);

	/**
	 * @inheritDoc
	 */
	abstract public function deleteFile($path);

	/**
	 * @inheritDoc
	 */
	abstract public function renameFile($oldPath, $newPath);

	/**
	 * @inheritDoc
	 */
	abstract public function mkDir($path);

	/**
	 * @inheritDoc
	 */
	abstract public function rmDir($path);

}
