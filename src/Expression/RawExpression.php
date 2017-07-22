<?php

namespace OomphInc\FAST_WP\Expression;

class RawExpression implements ExpressionInterface {

	public $expression;

	public function __construct($expression) {
		$this->expression = $expression;
	}

	public function compile() {
		return (string) $this->expression;
	}

}
