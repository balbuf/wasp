<?php

namespace OomphInc\WASP\Compilable;

class HookExpression extends BaseCompilable {

	public $name;
	public $expressions = [];
	public $priority = 10;
	public $args = [];
	public $function = 'add_action';

	public function compile() {
		return $this->transformer->create('FunctionExpression', [
			'name' => $this->function,
			'args' => [
				$this->name,
				$this->transformer->create('BlockExpression', [
					'name' => 'function',
					'parenthetical' => $this->transformer->create('RawExpression', [
						'expression' => implode(', ', preg_replace('/^[^$].+$/', '$$0', $this->args)),
					]),
					'expressions' => $this->expressions,
					'inline' => true,
				]),
				$this->priority,
				count($this->args),
			],
		])->compile();
	}

}
