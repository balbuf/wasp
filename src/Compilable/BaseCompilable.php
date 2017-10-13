<?php

namespace OomphInc\WASP\Compilable;

abstract class BaseCompilable implements CompilableInterface {

	protected $transformer;

	public function __construct($transformer, array $values = []) {
		$this->transformer = $transformer;
		$this->set($values);
	}

	abstract public function compile();

	public function set($values) {
		$values = func_num_args() === 1 ? $values : [$values => func_get_arg(1)];
		foreach ($values as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}

}
