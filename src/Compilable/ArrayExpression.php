<?php

namespace OomphInc\WASP\Compilable;

class ArrayExpression extends BaseCompilable {

	public $array = [];

	public function compile() {
		return "[\n" . implode(",\n", array_map(function($key, $value) {
				return preg_replace('/^.+/m', "\t\$0", var_export($key, true) . ' => ' . $this->transformer->compile($value));
			}, array_keys($this->array), $this->array)) . ",\n]";
	}

}
