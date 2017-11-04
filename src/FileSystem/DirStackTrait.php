<?php

namespace OomphInc\WASP\FileSystem;

trait DirStackTrait {

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

	abstract public function resolvePath($cwd);

}
