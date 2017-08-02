<?php

namespace OomphInc\WASP\Compilable;

class ArrayExpression implements CompilableInterface {

	public $array;

	public function __construct(array $array) {
		$this->array = $array;
	}

	public function compile($transformer) {
		return "[\n" . implode(",\n", array_map(function($key, $value) use ($transformer) {
				return preg_replace('/^.+/m', "\t\$0", var_export($key, true) . ' => ' . $transformer->compile($value));
			}, array_keys($this->array), $this->array)) . "\n]";
	}

}
