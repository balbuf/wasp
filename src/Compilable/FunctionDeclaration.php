<?php

namespace OomphInc\WASP\Compilable;

class FunctionDeclaration extends BaseCompilable {

	public $name;
	public $args = []; // either arg names in an array, or a compilable
	public $use = []; // variable names to import from outer scope
	public $expressions = [];
	public $methodModifiers;
	public $body;
	public $type;

	public function compile() {
		$declaration = ($this->methodModifiers ? $this->methodModifiers . ' ' : '')
			. 'function ' . ($this->name ? $this->name : '') . '(';

		// add arguments
		if (is_array($this->args) && count($this->args)) {
			$declaration .= ' ' . $this->compileArgs($this->args) . ' ';
		} else if ($this->args instanceof CompilableInterface) {
			$declaration .= $this->args->compile();
		}

		$declaration .= ')';

		// add a use statement?
		if (!$this->name && count($this->use)) {
			$declaration .= ' use ( ' . $this->compileArgs(array_unique($this->use)) . ' )';
		}

		// for the function body, the body and type properties take precedence over expressions
		if (isset($this->body, $this->type)) {
			// body type
			switch ($this->type) {
				// straight callable
				case 'callable':
					// convert array callable to string callable
					if (is_array($this->body) && array_keys($this->body) === [0, 1]) {
						$this->body = implode('::', $this->body);
					}

					$body = 'return ' . $this->body . '( ' . $this->compileArgs($this->args) . ' );';
					break;

				// template file
				case 'template':
					$body = 'return require ' . $this->transformer->outputExpression->convertPath($this->body) . ';';
					break;

				// raw php
				case 'php':
					$body = $this->body;
					break;

				// html
				case 'html':
					$body = 'echo' . var_export($this->body, true) . ';';
					break;

				case 'return':
					$body = 'return ' . $this->transformer->compile($this->body) . ';';
					break;

				default:
					$body = '';
			}
		} else {
			$body = $this->transformer->create('CompositeExpression', ['expressions' => $this->expressions])->compile();
		}

		return "{$declaration} {\n"
			. rtrim(preg_replace('/^.+/m', "\t\$0", $body), "\n")
			. "\n}" . ($this->name ? "\n" : '');
	}

	/**
	 * Compile an array of accepted args into a comma-delimited args string.
	 * @param  array  $args  accepted args, optionally prepended by a $
	 * @return string        compiled args string
	 */
	protected function compileArgs( array $args ) {
		return implode(', ', preg_replace('/^[^$].+$/', '$$0', $args));
	}

}
