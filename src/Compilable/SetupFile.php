<?php

namespace OomphInc\WASP\Compilable;

class SetupFile implements CompilableInterface {

	public $regular;
	public $lazy;

	public function __construct() {
		$this->regular = new CompositeExpression([], "\n\n");
		$this->lazy = new CompositeExpression([], "\n\n");
	}

	//hook to place this code inside of a particular action, optional priority for that action
	public function add_expression(CompilableInterface $expression, $hook = null, $priority = 10, $prop = 'regular') {
		if ($hook) {
			$index = serialize([$hook, $priority]);
			if (!isset($this->$prop->expressions[$index])) {
				$this->$prop->expressions[$index] = new HookExpression($hook, [], $priority);
			}
		} else {
			$index = 'bare';
			if (!isset($this->$prop->expressions[$index])) {
				// make sure bare expressions come first
				$this->$prop->expressions = array_merge([$index => new CompositeExpression([], "\n\n")], $this->$prop->expressions);
			}
		}
		$this->$prop->expressions[$index]->expressions[] = $expression;
	}

	public function add_lazy_expression(CompilableInterface $expression, $hook = null, $priority = 10) {
		$this->add_expression($expression, $hook, $priority, 'lazy');
	}

	public function compile($transformer) {
		// @todo: perhaps grab a template that includes a comment about what the file is
		$compiled = ["<?php\n", $this->regular->compile($transformer)];

		if (!empty($this->lazy->expressions)) {
			$about = $transformer->get_property('about');
			$version = isset($about['version']) ? (string) $about['version'] : '';

			$compiled[] = (new BlockExpression('if',
				new CompositeExpression([new FunctionExpression('get_option', ['wasp_version'], true), new RawExpression('!=='), $version], ' '),
				[$this->lazy, new FunctionExpression('update_option', ['wasp_version', $version])]
			))->compile($transformer);
		}

		return implode("\n", array_filter($compiled));
	}

}
