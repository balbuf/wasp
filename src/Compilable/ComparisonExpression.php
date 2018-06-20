<?php

namespace OomphInc\WASP\Compilable;

class ComparisonExpression extends BaseCompilable {

	public $value = null;
	public $comparison = null;
	public $operator = 'equal';
	public $negate = false;

	// standard operators
	static public $operators = [
		'equal' => '%value === %comparison',
		'equalWeak' => '%value == %comparison',
		'notEqual' => '%value !== %comparison',
		'notEqualWeak' => '%value != %comparison',
		'lessThan' => '%value < %comparison',
		'lessThanEqual' => '%value <= %comparison',
		'greaterThan' => '%value > %comparison',
		'greaterThanEqual' => '%value >= %comparison',
		'in' => 'in_array( %value, %comparison, true )',
		'inWeak' => 'in_array( %value, %comparison )',
		'matches' => 'preg_match( %comparison, %value ) > 0',
		'truthy' => '(bool) %value',
		'falsey' => '! %value',
	];

	public function compile() {
		if (!isset(static::$operators[$this->operator])) {
			return "/* invalid operator: {$operator} */";
		}

		// fill in placeholders
		$expression = str_replace(
			['%value', '%comparison'],
			[
				$this->transformer->compile($this->value),
				$this->transformer->compile($this->comparison),
			],
			static::$operators[$this->operator]
		);

		// negate the expression?
		if ($this->negate) {
			$expression = "! ( {$expression} )";
		}

		return $expression;
	}

}
