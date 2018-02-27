<?php

namespace OomphInc\WASP\Compilable;

class HookExpression extends BaseCompilable {

	public $name;
	public $expressions = [];
	public $priority = 10;
	public $args = [];
	public $function = 'add_action';
	public $use = [];

	public function compile() {
		return $this->transformer->create('FunctionExpression', [
			'name' => $this->function,
			'args' => [
				$this->name,
				$this->transformer->create('FunctionDeclaration', [
					'args' => $this->args,
					'use' => $this->use,
					'expressions' => $this->expressions,
				]),
				$this->priority,
				count($this->args),
			],
		])->compile();
	}

}
