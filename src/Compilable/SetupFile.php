<?php

namespace OomphInc\FAST_WP\Compilable;

class SetupFile implements CompilableInterface {

	protected $expressions = [];
	protected $expressions_lazy = [];

	//hook to place this code inside of a particular action, optional priority for that action
	public function add_expression(CompilableInterface $expression, $hook = null, $priority = 10) {
		if ($hook) {
			$this->expressions[$hook][$priority][] = $expression;
		} else {
			$this->expressions[''][] = $expression;
		}
	}

	public function add_lazy_expression(CompilableInterface $expression, $hook = null, $priority = 10) {
		if ($hook) {
			$this->expressions_lazy[$hook][$priority][] = $expression;
		} else {
			$this->expressions_lazy[''][] = $expression;
		}
	}

	public static function compile_expressions($transformer, $expressions) {
		$compiled = '';
		foreach ($expressions as $hook => $priorities) {
			if ($hook === '') {
				$compiled .= implode(array_map(function ($expression) use ($transformer) {
					return $transformer->compile($expression);
				}, $priorities));
			} else {
				foreach ($priorities as $priority => $expression_objects) {
					$compiled .= (new HookExpression($hook, $expression_objects, $priority))->compile($transformer);
				}
			}
		}
		return $compiled . "\n";
	}

	public function compile($transformer) {
		// @todo: perhaps grab a template that includes a comment about what the file is
		$compiled = "<?php\n\n";

		if (!empty($this->expressions)) {
			$compiled .= static::compile_expressions($transformer, $this->expressions);
		}

		if (!empty($this->expressions_lazy)) {
			$version = (string) $transformer->get_property('version');
			$expressions = $this->expressions_lazy;
			// update the version option in the db
			$expressions[''][] = new FunctionExpression('update_option', ['fast_wp_version', $version]);

			$compiled .= (new BlockExpression('if',
				new CompositeExpression([new FunctionExpression('get_option', ['fast_wp_version'], true), new RawExpression('!=='), $version], ' '),
				[new RawExpression(static::compile_expressions($transformer, $expressions))]
			))->compile($transformer);
		}

		return $compiled;
	}

}
