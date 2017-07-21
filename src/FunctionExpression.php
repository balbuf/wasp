<?php

namespace Oomph\YAWC\FunctionExpression;

class FunctionExpression implements ExpressionInterface {
	
	public $name;
	protected $args;

	public function __construct( $name, $args ) {
		$this->name = $name;
		$this->args = $args;
	}

	public function compile() {
		return "{$function_name}( " . implode( ', ', array_map( function ( $arg ) {
			return var_export( $arg, true );
		}, $$args ) ) . ");\n";
	}
}
