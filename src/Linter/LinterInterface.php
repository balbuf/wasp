<?php

namespace OomphInc\WASP\Linter;

interface LinterInterface {

	/**
	 * Lint the given code.
	 * @param  string $code code to lint
	 * @throws Exception  if the code fails to lint successfully
	 */
	public function lint($code);

}
