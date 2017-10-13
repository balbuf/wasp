<?php

namespace OomphInc\WASP\Compilable;

class CompositeExpression extends BaseCompilable {

	public $expressions = [];
	public $joiner = '';

	public function compile() {
		return implode($this->joiner, array_map(function ($expression) {
				return $this->transformer->compile($expression);
			}, $this->expressions));
	}

}
