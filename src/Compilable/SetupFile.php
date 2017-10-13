<?php

namespace OomphInc\WASP\Compilable;

class SetupFile implements CompilableInterface {

	protected $transformer;
	public $regular;
	public $lazy;

	public function __construct($transformer) {
		$this->transformer = $transformer;
		$this->regular = $transformer->create('CompositeExpression', ['joiner' => "\n\n"]);
		$this->lazy = $transformer->create('CompositeExpression', ['joiner' => "\n\n"]);
	}

	/**
	 * Add an expression to the setup file, optionally inside of a hook.
	 * @param CompilableInterface $expression compilable expression
	 * @param string              $hook       optional hook
	 * @param integer             $priority   priority for hook
	 * @param string              $prop       property to store expression (regular or lazy)
	 */
	public function add_expression(CompilableInterface $expression, $hook = null, $priority = 10, $prop = 'regular') {
		if ($hook) {
			$index = serialize([$hook, $priority]);
			if (!isset($this->$prop->expressions[$index])) {
				$this->$prop->expressions[$index] = $this->transformer->create('HookExpression', ['name' => $hook, 'priority' => $priority]);
			}
		} else {
			$index = 'bare';
			if (!isset($this->$prop->expressions[$index])) {
				// make sure bare expressions come first
				$this->$prop->expressions = array_merge([$index => $this->transformer->create('CompositeExpression', ['joiner' => "\n\n"])], $this->$prop->expressions);
			}
		}
		$this->$prop->expressions[$index]->expressions[] = $expression;
	}

	/**
	 * Add an expression to the setup file, that only runs upon a setup file change, optionally inside of a hook.
	 * @param CompilableInterface $expression compilable expression
	 * @param string              $hook       optional hook
	 * @param integer             $priority   priority for hook
	 */
	public function add_lazy_expression(CompilableInterface $expression, $hook = null, $priority = 10) {
		$this->add_expression($expression, $hook, $priority, 'lazy');
	}

	public function compile() {
		// @todo: perhaps grab a template that includes a comment about what the file is
		$compiled = ["<?php\n", $this->regular->compile()];

		if (!empty($this->lazy->expressions)) {
			$lazy_compiled = $this->lazy->compile();
			$hash = md5($lazy_compiled);
			$option = 'wasp_version_' . $this->transformer->get_property('about', 'name');

			$compiled[] = $this->transformer->create('BlockExpression', [
				'name' => 'if',
				'parenthetical' => $this->transformer->create('CompositeExpression', [
					'joiner' => ' ',
					'expressions' => [
						$this->transformer->create('FunctionExpression', [
							'name' => 'get_option',
							'args' => [$option],
							'inline' => true,
						]),
						$this->transformer->create('RawExpression', ['expression' => '!==']),
						$hash,
					],
				]),
				'expressions' => [
					$this->transformer->create('RawExpression', [
						'expression' => $lazy_compiled,
					]),
					$this->transformer->create('FunctionExpression', [
						'name' => 'update_option',
						'args' => [$option, $hash],
					]),
				],
			])->compile();
		}

		return implode("\n", array_filter($compiled));
	}

}
