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
		return (new FunctionExpression('add_action', [
			$this->name,
			new BlockExpression('function', new RawExpression, $this->expressions),
			$this->priority,
			(int) $this->num_args,
		]))->compile($transformer);
	}

}
