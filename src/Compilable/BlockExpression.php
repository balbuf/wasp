<?php

namespace OomphInc\WASP\Compilable;

class BlockExpression extends BaseCompilable {

	public $name;
	public $parenthetical = null;
	public $expressions = [];
	public $inline = false;

	public function compile() {
		return $this->name . ($this->parenthetical !== null ? ' ( ' . $this->transformer->compile($this->parenthetical) . ' )' : '') . " {\n"
			. rtrim(preg_replace('/^.+/m', "\t\$0", $this->transformer->create('CompositeExpression', ['expressions' => $this->expressions])->compile()), "\n")
			. "\n}" . ($this->inline ? '' : "\n");
	}

}
