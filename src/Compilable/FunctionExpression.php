<?php

namespace OomphInc\FAST_WP\Compilable;

class FunctionExpression implements CompilableInterface {

	public $name;
	public $args;
	public $inline;

	public function __construct($name, array $args, $inline = false) {
		$this->name = $name;
		$this->args = $args;
		$this->inline = $inline;
	}

	public function compile($transformer) {
	$compiled = $this->name . '(';
		if (!empty($this->args)) {
			$compiled .= ' ' . implode(', ', array_map(function ($arg) use ($transformer) {
				return $transformer->compile($arg);
			}, $this->args)) . ' ';
		}
		return $compiled . ')' . ($this->inline ? '' : ";\n");
	}

}
