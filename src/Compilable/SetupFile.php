<?php

namespace OomphInc\WASP\Compilable;

use RuntimeException;
use OomphInc\WASP\FileSystem\FileSystemHelper;
use OomphInc\WASP\Wasp;

class SetupFile implements CompilableInterface {

	protected $transformer;
	protected $dir;
	public $regular;
	public $lazy;

	public function __construct($transformer) {
		$this->transformer = $transformer;

		// get the setup file dir
		$this->dir = $transformer->getProperty(Wasp::META_PROPERTY, 'dir') ?: '';
		// check for upward path component
		if (in_array('..', FileSystemHelper::getDirParts($this->dir), true)) {
			throw new RuntimeException('Setup file dir path cannot contain an upward path component "../"');
		}

		$this->regular = $transformer->create('CompositeExpression', ['joiner' => "\n\n"]);
		$this->lazy = $transformer->create('CompositeExpression', ['joiner' => "\n\n"]);
	}

	/**
	 * Add an expression to the setup file.
	 * @param CompilableInterface $expression compilable expression
	 * @param array               $options   additional options to control the placement
	 */
	public function addExpression(CompilableInterface $expression, $options = []) {
		// default options
		$options += [
			'hook' => null,
			'lazy' => false,
			'use' => [],
			'args' => [],
		];
		$prop = $options['lazy'] ? 'lazy' : 'regular';

		// place expression inside of a hook
		if ($hook = $options['hook']) {
			$priority = isset($options['priority']) ? (string) $options['priority'] : '10';
			$index = serialize([$hook, $priority]);
			// create a container for the given hook and priority
			if (!isset($this->$prop->expressions[$index])) {
				$this->$prop->expressions[$index] = $this->transformer->create('HookExpression', [
					'name' => $hook,
					'priority' => $priority,
				]);
			}
			$this->$prop->expressions[$index]->expressions[] = $expression;
			// add any "use" values
			if (count($options['use'])) {
				$this->$prop->expressions[$index]->use = array_merge($this->$prop->expressions[$index]->use, $options['use']);
			}
			// set the args values, if we are asking for more than what we had previously
			if (count($options['args']) > count($this->$prop->expressions[$index]->args)) {
				$this->$prop->expressions[$index]->args = $options['args'];
			}
		} else {
			$priority = isset($options['priority']) ? (string) $options['priority'] : '100';
			// create a container for bare expressions
			if (!isset($this->$prop->expressions['bare'])) {
				// make sure bare expressions come first
				$this->$prop->expressions = array_merge(
					['bare' => $this->transformer->create('CompositeExpression', ['joiner' => "\n\n"])],
					$this->$prop->expressions
				);
			}
			// create a container for bare expressions of the given priority
			if (!isset($this->$prop->expressions['bare']->expressions[$priority])) {
				$this->$prop->expressions['bare']->expressions[$priority] = $this->transformer->create('CompositeExpression', ['joiner' => "\n\n"]);
				// keep the array sorted by priority
				ksort($this->$prop->expressions['bare']->expressions, SORT_NUMERIC);
			}
			$this->$prop->expressions['bare']->expressions[$priority]->expressions[] = $expression;
		}
	}

	/**
	 * Convert a path that is relative to the file root to one that is relative to the setup file.
	 * @param  string $path  path relative to file root
	 * @return string       fully qualified path relative to setup file
	 */
	public function convertPath($path) {
		return '__DIR__ . ' . var_export(FileSystemHelper::relativePath($this->dir, $path), true);
	}

	public function compile() {
		// @todo: perhaps grab a template that includes a comment about what the file is
		$compiled = ["<?php\n", $this->regular->compile()];

		if (!empty($this->lazy->expressions)) {
			$lazyCompiled = $this->lazy->compile();
			$hash = md5($lazyCompiled);
			$option = 'wasp_version_' . $this->transformer->getProperty(Wasp::META_PROPERTY, 'prefix');

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
						'expression' => $lazyCompiled,
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
