<?php

namespace OomphInc\FAST_WP\Compilable;

class CompositeExpression implements CompilableInterface {

	public $expressions = [];
	public $joiner;

	public function __construct($expressions, $joiner = '') {
		$this->expressions = $expressions;
		$this->joiner = $joiner;
	}

	public function compile($transformer) {
		return implode($this->joiner, array_map(function ($expression) use ($transformer) {
				return $transformer->compile($expression);
			}, $this->expressions));
	}

}
