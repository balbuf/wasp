<?php

namespace OomphInc\FAST_WP\Compilable;

class RawExpression implements CompilableInterface {

	public $expression;

	public function __construct($expression = '') {
		$this->expression = $expression;
	}

	public function compile($transformer) {
		return (string) $this->expression;
	}

}
