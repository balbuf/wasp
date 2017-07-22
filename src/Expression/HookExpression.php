<?php

namespace OomphInc\FAST_WP\Expression;

class HookExpression implements ExpressionInterface {

	public $name;
	public $expressions = [];
	public $priority;
	public $num_args;

	public function __construct($name, $priority = 10, $num_args = 99) {

	}

	public function compile() {

	}

}
