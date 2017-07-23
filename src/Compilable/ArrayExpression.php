<?php

namespace OomphInc\FAST_WP\Compilable;

class ArrayExpression implements CompilableInterface {

	public $array;

	public function __construct(array $array) {
		$this->array = $array;
	}

	public function compile($transformer) {
		return '[' . implode(', ', array_map(function($key, $value) use ($transformer) {
				return var_export($key, true) . ' => ' . $transformer->compile($value);
			}, array_keys($this->array), $this->array)) . ']';
	}

}
