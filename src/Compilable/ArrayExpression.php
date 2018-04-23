<?php

namespace OomphInc\WASP\Compilable;

class ArrayExpression extends BaseCompilable {

	public $array = [];
	public $inline = false;

	public function compile() {
		if (empty($this->array)) {
			return '[]';
		}

		// check if this is an associative array or not
		$isAssoc = array_keys($this->array) !== range(0, count($this->array) - 1);
		return ($this->inline ? '[' : "[\n") . implode($this->inline ? ', ' : ",\n", array_map(function($key, $value) use ($isAssoc) {
				$item = ($isAssoc ? var_export($key, true) . ' => ' : '') . $this->transformer->compile($value);
				return $this->inline ? $item : preg_replace('/^.+/m', "\t\$0", $item);
			}, array_keys($this->array), $this->array)) . ($this->inline ? ']' : ",\n]");
	}

}
