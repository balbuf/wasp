<?php

namespace OomphInc\WASP\Compilable;

class FunctionDeclaration extends BaseCompilable {

	public $name;
	public $args = []; // either arg names in an array, or a compilable
	public $use = []; // variable names to import from outer scope
	public $expressions = [];

	public function compile() {
		$declaration = 'function ' . ($this->name ? $this->name : '') . '(';

		// add arguments
		if (is_array($this->args) && count($this->args)) {
			$declaration = ' ' . implode(', ', preg_replace('/^[^$].+$/', '$$0', $this->args)) . ' ';
		} else if ($this->args instanceof CompilableInterface) {
			$declaration .= $this->args->compile();
		}

		$declaration .= ')';

		// add a use statement?
		if (!$this->name && count($this->use)) {
			$declaration .= ' use ( ' . implode(', ', preg_replace('/^[^$].+$/', '$$0', array_unique($this->use))) . ' )';
		}

		return "{$declaration} {\n"
			. rtrim(preg_replace('/^.+/m', "\t\$0", $this->transformer->create('CompositeExpression', ['expressions' => $this->expressions])->compile()), "\n")
			. "\n}" . ($this->name ? "\n" : '' );
	}

}
