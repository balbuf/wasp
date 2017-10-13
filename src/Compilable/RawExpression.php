<?php

namespace OomphInc\WASP\Compilable;

class RawExpression extends BaseCompilable {

	public $expression = '';

	public function compile() {
		return (string) $this->expression;
	}

}
