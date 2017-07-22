<?php

namespace OomphInc\FAST_WP\Expression;

class FunctionExpression implements ExpressionInterface {

	public $name;
	public $args;

	public function __construct($name, array $args) {
		$this->name = $name;
		$this->args = $args;
	}

	public function compile() {
		return $this->name . '(' . implode(', ', array_map( function ( $arg ) {
			return var_export( $arg, true );
		}, $this->args)) . ')';
	}

}
