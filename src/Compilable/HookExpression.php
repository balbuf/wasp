<?php

namespace OomphInc\FAST_WP\Compilable;

class HookExpression implements CompilableInterface {

	public $name;
	public $expressions = [];
	public $priority;
	public $num_args;

	public function __construct($name, $expressions, $priority = 10, $num_args = 99) {
		$this->name = $name;
		$this->expressions = $expressions;
		$this->priority = $priority;
		$this->num_args = $num_args;
	}

	public function compile($transformer) {
		return 'add_action( ' . var_export($this->name, true) . ", function() {\n"
			. implode(array_map(function ($expression) use ($transformer) {
				return "\t" . $transformer->compile($expression);
			}, $this->expressions))
			. "\n}, " . var_export($this->priority, true) . ', ' . (int) $this->num_args . " );\n";
	}

}
