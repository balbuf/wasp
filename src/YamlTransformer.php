<?php
// register all the transform methods onto here

namespace OomphInc\WASP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use OomphInc\WASP\Compilable\SetupFile;
use OomphInc\WASP\Compilable\CompilableInterface;
use OomphInc\WASP\Event\Events;
use OomphInc\WASP\Event\ValueEvent;
use Symfony\Component\EventDispatcher\GenericEvent;
use RuntimeException;
use InvalidArgumentException;
use OomphInc\WASP\Handler\HandlerInterface;

class YamlTransformer {

	protected $yamlString;
	protected $handlers = [];
	protected $defaultsCallables = [];
	protected $classes = [];
	protected $vars = [];
	protected $dispatcher;
	protected $logger;
	protected $propertyTree;
	public $outputExpression;

	/**
	 * @param string $yaml_string contents of YAML configuration
	 */
	public function __construct($yamlString, $dispatcher, $logger, $propertyTree) {
		$this->propertyTree = $propertyTree;
		$this->setYaml($yamlString);
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;

		$event = new GenericEvent();
		$event->setArgument('transformer', $this);
		$dispatcher->dispatch(Events::TRANSFORMER_SETUP, $event);

		$this->outputExpression = $this->create('SetupFile');
	}

	/**
	 * Set the YAML string and parse.
	 * @param string $yamlString YAML string
	 */
	public function setYaml($yamlString) {
		$this->yamlString = $yamlString;
		// try to parse the string (will throw an exception on parse error, caught by the application)
		$yaml = Yaml::parse($yamlString);
		if (!is_array($yaml)) {
			throw new RuntimeException('Invalid YAML file');
		}
		$this->propertyTree->set($yaml);
	}

	/**
	 * Add a transform handler.
	 * @param HandlerInterface  $handler   handler object
	 */
	public function setHandler(HandlerInterface $handler) {
		foreach ((array) $handler->getSubscribedProperties() as $property) {
			$this->handlers[$property][$handler->getIdentifier($property)] = $handler;
		}
	}

	/**
	 * Remove the transform handler matching the given property and identifier.
	 * @param  string $property   YAML property
	 * @param  string $identifier handler indentifier
	 */
	public function removeHandler($property, $identifier) {
		unset($this->handlers[$property][$identifier]);
	}

	/**
	 * Set or unset a compilable class.
	 * @param string $name  name used to create a new object of the class
	 * @param string $class fully qualified class name
	 */
	public function setClass($name, $class) {
		if (!class_exists($class)) {
			throw new RuntimeException("Class does not exist: $class");
		}
		$this->classes[$name] = $class;
	}

	/**
	 * Unset a compilable class.
	 * @param  string $name  name of compilable
	 */
	public function removeClass($name) {
		unset($this->classes[$name]);
	}

	/**
	 * Create a new instance of a compilable class.
	 * @param  string $name name of compilable type
	 * @return CompilableInterface  instantiated compilable class
	 */
	public function create($name, $args = []) {
		$class = isset($this->classes[$name]) ? $this->classes[$name] : __NAMESPACE__ . "\\Compilable\\$name";
		if (class_exists($class)) {
			return new $class($this, $args);
		}
		throw new RuntimeException("No class exists for '$name'");
	}

	/**
	 * Get the parsed YAML config for the given property chain, if set.
	 * This is a convenience wrapper around the PropertyTree::get method.
	 * @param  string [$property...] property name
	 * @return mixed            config value, if set
	 */
	public function getProperty() {
		return $this->propertyTree->get(func_get_args());
	}

	/**
	 * Get the property tree object.
	 * @return PropertyTree      property tree object
	 */
	public function getPropertyTree() {
		return $this->propertyTree;
	}

	/**
	 * Set a variable for use throughout the generation.
	 * @param string $var   variable name
	 * @param mixed  $value
	 */
	public function setVar($var, $value) {
		$this->vars[$var] = $value;
	}

	/**
	 * Get a variable.
	 * @param  string $var variable name
	 * @return mixed       variable value
	 */
	public function getVar($var) {
		if (isset($this->vars[$var])) {
			return $this->vars[$var];
		}
	}

	/**
	 * Compile a compilable expression.
	 * @param  mixed  $expression compilable expression object or any kind of data
	 * @return string             the compiled expression
	 */
	public function compile($expression) {
		$expression = $this->dispatcher->dispatch(Events::PRE_COMPILE, new ValueEvent($expression))->getValue();

		if ($expression instanceof CompilableInterface) {
			$compiled = $expression->compile();
		} else if (is_array($expression)) {
			$compiled = $this->create('ArrayExpression', ['array' => $expression])->compile();
		} else {
			$compiled = var_export($expression, true);
		}

		return $this->dispatcher->dispatch(Events::POST_COMPILE, new ValueEvent($compiled, ['expression' => $expression]))->getValue();
	}

	/**
	 * Process the configuration file, calling all transform handlers.
	 * @param  array  $disabledHandlers  identifiers of handlers to disable
	 * @return string  compiled file
	 */
	public function execute(array $disabledHandlers = []) {
		$this->dispatcher->dispatch(Events::PRE_TRANSFORM);

		// only iterate on top-level properties that were explicitly set
		foreach (array_keys($this->propertyTree->getRaw()) as $property) {
			// do we have any handlers?
			if (empty($this->handlers[$property])) {
				$this->logger->notice("No handlers for property '$property'");
				continue;
			}

			// process all handler defaults first
			foreach ($this->handlers[$property] as $identifier => $handler) {
				// are we skipping this handler?
				if (in_array($identifier, $disabledHandlers, true)) {
					continue;
				}
				$this->propertyTree->setDefault($identifier, $property, $handler->getDefaults($property));
			}

			// execute each handler
			foreach ($this->handlers[$property] as $identifier => $handler) {
				// skip handler?
				if (in_array($identifier, $disabledHandlers, true)) {
					$this->logger->info("Skipping handler '$identifier'");
					continue;
				}
				$this->logger->info("Executing handler '$identifier'");
				$handler->handle($this, $this->propertyTree->get($property), $property);
			}
		}

		$compiled = $this->compile($this->outputExpression);

		$this->dispatcher->dispatch(Events::POST_TRANSFORM);
		return $compiled;
	}

}
