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
		return $this->name . ($this->parenthetical !== null ? ' (' . $transformer->compile($this->parenthetical) . ')' : '') . " {\n"
			. rtrim(preg_replace('/^.+/m', "\t\$0", (new CompositeExpression($this->expressions))->compile($transformer)), "\n")
			. "\n}\n";
	}

}
