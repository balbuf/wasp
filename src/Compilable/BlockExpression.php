<?php

namespace OomphInc\WASP\Compilable;

class BlockExpression extends BaseCompilable {

	public $name;
	public $parenthetical = null;
	public $expressions = [];
	public $inline = false;

	public function compile() {
		$parenthetical = $this->parenthetical !== null ? ' (' . preg_replace('/^.+$/', ' $0 ', $this->transformer->compile($this->parenthetical)) . ')' : '';
		return $this->name . $parenthetical . " {\n"
			. rtrim(preg_replace('/^.+/m', "\t\$0", $this->transformer->create('CompositeExpression', ['expressions' => $this->expressions])->compile()), "\n")
			. "\n}" . ($this->inline ? '' : "\n");
	}

}
