<?php
// register all the transform methods onto here

namespace OomphInc\WASP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use OomphInc\WASP\Compilable\SetupFile;
use OomphInc\WASP\Compilable\CompilableInterface;
use OomphInc\WASP\Events;
use Symfony\Component\EventDispatcher\GenericEvent;

class YamlTransformer {

	protected $yaml_string;
	protected $yaml;
	protected $handlers = [];
	protected $application;
	public $setup_file;

	/**
	 * @param string $yaml_string contents of YAML configuration
	 */
	public function __construct($yaml_string, $application) {
		$this->yaml_string = $yaml_string;
		// try to parse the string
		try {
			$this->yaml = Yaml::parse($yaml_string);
		} catch (ParseException $e) {
			$application->services->logger->error('Unable to parse the YAML string: ' . $e->getMessage());
			return;
		}
		$this->application = $application;
		$this->setup_file = new SetupFile();
	}

	/**
	 * Add a transform handler for a given top-level YAML property.
	 * @param string   $property   property to handle
	 * @param string   $identifier unique identifier for this handler
	 * @param callable $handler    the handler that will be invoked when the property is encountered
	 */
	public function add_handler($property, $identifier, callable $handler) {
		$this->handlers[$property][$identifier] = $handler;
	}

	/**
	 * Remove the transform handler matching the given property and identifier.
	 * @param  string $property   YAML property
	 * @param  string $identifier handler indentifier
	 */
	public function remove_handler($property, $identifier) {
		unset($this->handlers[$property][$identifier]);
	}

	/**
	 * Get the parsed YAML config for the given top-level property, if set.
	 * @param  string $property property name
	 * @return mixed            config value, if set
	 */
	public function get_property($property) {
		if (isset($this->yaml[$property])) {
			return $this->yaml[$property];
		}
	}

	/**
	 * Set the value for a top-level property within the YAML config.
	 * @param string $property property name
	 * @param mixed  $value    data
	 */
	public function set_property($property, $value) {
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
			$compiled = $expression->compile($this);
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
				$this->application->services->logger->warning("No handler(s) for property '$property'\n");
			}
		}

		$compiled = $this->compile($this->setup_file);

		$this->application->services->dispatcher->dispatch(Events::POST_TRANSFORM);
		return $compiled;
	}

}
