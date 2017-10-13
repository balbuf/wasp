<?php

namespace OomphInc\WASP\Compilable;

class HookExpression extends BaseCompilable {

	public $name;
	public $expressions = [];
	public $priority = 10;
	public $numArgs = 99;

	public function compile() {
		return $this->transformer->create('FunctionExpression', ['name' => 'add_action', 'args' => [
			$this->name,
			$this->transformer->create('BlockExpression', [
				'name' => 'function',
				'parenthetical' => $this->transformer->create('RawExpression'),
				'expressions' => $this->expressions,
				'inline' => true,
			]),
			$this->priority,
			(int) $this->numArgs,
		]])->compile();
	}

}
