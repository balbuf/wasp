<?php

namespace OomphInc\WASP\Compilable;

class FunctionExpression extends BaseCompilable {

	public $name;
	public $args = [];
	public $inline = false;

	public function compile() {
		$compiled = $this->name . '(';
		if (!empty($this->args)) {
			$compiled .= ' ' . implode(', ', array_map(function ($arg) {
				return $this->transformer->compile($arg);
			}, $this->args)) . ' ';
		}
		return $compiled . ')' . ($this->inline ? '' : ";\n");
	}

}
