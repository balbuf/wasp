<?php
// register all the transform methods onto here

namespace OomphInc\WASP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use OomphInc\WASP\Compilable\SetupFile;
use OomphInc\WASP\Compilable\CompilableInterface;
use OomphInc\WASP\Events;
use Symfony\Component\EventDispatcher\GenericEvent;
use RuntimeException;

class YamlTransformer {

	protected $yamlString;
	protected $yaml;
	protected $handlers = [];
	protected $classes = [];
	protected $application;
	public $setupFile;

	/**
	 * @param string $yaml_string contents of YAML configuration
	 */
	public function __construct($yamlString, $application) {
		$this->yamlString = $yamlString;
		// try to parse the string (will throw an exception on parse error, caught by the application)
		$this->yaml = Yaml::parse($yamlString);
		if (!is_array($this->yaml)) {
			throw new RuntimeException('Invalid YAML file');
		}
		$this->application = $application;
		$event = new GenericEvent();
		$event->setArgument('transformer', $this);
		$application->services->dispatcher->dispatch(Events::PRE_SETUP_FILE, $event);
		$this->setupFile = $this->create('SetupFile');
	}

	/**
	 * Add a transform handler for a given top-level YAML property.
	 * @param string   $property   property to handle
	 * @param string   $identifier unique identifier for this handler
	 * @param callable $handler    the handler that will be invoked when the property is encountered
	 */
	public function setHandler($property, $identifier, callable $handler) {
		$this->handlers[$property][$identifier] = $handler;
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
	 * Set handlers based on the public methods of the given class.
	 * Each method should be named after the top-level property it will handle.
	 * @param  string|object  $class     class name (for static methods) or instantiated object (for non static)
	 * @param  string  $prefix           prefix for the handler identifier
	 * @param  boolean $convertFromCamel whether to convert the method names from camelCase to snake_case
	 */
	public function importHandlersFromClass($class, $prefix, $convertFromCamel = true) {
		foreach (get_class_methods($class) as $method) {
			$handler = $convertFromCamel ? strtolower(preg_replace('/[A-Z]/', '_$0', $method)) : $method;
			$this->setHandler($handler, $prefix . $handler, [$class, $method]);
		}
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
	 * @param  string [$property...] property name
	 * @return mixed            config value, if set
	 */
	public function getProperty() {
		$value = $this->yaml;
		foreach (func_get_args() as $key) {
			if (is_array($value) && isset($value[$key])) {
				$value = $value[$key];
			} else {
				return;
			}
		}
		return $value;
	}

	/**
	 * Set the value for a top-level property within the YAML config.
	 * @param string $property property name
	 * @param mixed  $value    data
	 */
	public function setProperty($property, $value) {
		$this->yaml[$property] = $value;
	}

	/**
	 * Compile a compilable expression.
	 * @param  mixed  $expression compilable expression object or any kind of data
	 * @return string             the compiled expression
	 */
	public function compile($expression) {
		$expression = $this->application->services->dispatcher->dispatch(Events::PRE_COMPILE, new GenericEvent($expression))->getSubject();

		if ($expression instanceof CompilableInterface) {
			$compiled = $expression->compile();
		} else if (is_array($expression)) {
			$compiled = $this->create('ArrayExpression', ['array' => $expression])->compile();
		} else {
			$compiled = var_export($expression, true);
		}

		return $this->application->services->dispatcher->dispatch(Events::POST_COMPILE, new GenericEvent($compiled, ['expression' => $expression]))->getSubject();
	}

	/**
	 * Process the configuration file, calling all transform handlers.
	 * @return string  compiled file
	 */
	public function execute() {
		$this->application->services->dispatcher->dispatch(Events::PRE_TRANSFORM);

		foreach ($this->yaml as $property => $data) {
			if (isset($this->handlers[$property])) {
				foreach ($this->handlers[$property] as $identifier => $handler) {
					call_user_func($handler, $this, $data);
				}
			} else {
				$this->application->services->logger->notice("No handler(s) for property '$property'");
			}
		}

		$compiled = $this->compile($this->setupFile);

		$this->application->services->dispatcher->dispatch(Events::POST_TRANSFORM);
		return $compiled;
	}

}
