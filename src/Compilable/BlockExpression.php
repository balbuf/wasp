<?php

namespace OomphInc\FAST_WP\Compilable;

class BlockExpression implements CompilableInterface {

	public $name;
	public $parenthetical;
	public $expressions;

	public function __construct($name, $parenthetical = null, $expressions) {
		$this->name = $name;
		$this->parenthetical = $parenthetical;
		$this->expressions = $expressions;
	}

	public function compile($transformer) {
		return $this->name . ($this->parenthetical ? '(' . $transformer->compile($this->parenthetical) . ')' : '')
			. " {\n" . implode(array_map(function ($expression) use ($transformer) {
				return "\t" . $transformer->compile($expression);
			}, $this->expressions)) . "\n}\n";
	}

}
