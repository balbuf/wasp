<?php

namespace OomphInc\WASP\Compilable;

class RawExpression extends BaseCompilable {

	public $expression = '';
	// search/replace tokens (replacements should be compilables)
	public $tokens = [];

	public function compile() {
		$expression = (string) $this->expression;

		// any tokens to replace?
		if (count($this->tokens)) {
			$expression = str_replace(
				array_keys($this->tokens),
				// compile the replacements
				array_map([$this->transformer, 'compile'], array_values($this->tokens)),
				$expression
			);
		}

		return $expression;
	}

}
