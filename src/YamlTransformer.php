<?php
// register all the transform methods onto here

namespace OomphInc\WASP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use OomphInc\WASP\Compilable\SetupFile;
use OomphInc\WASP\Compilable\CompilableInterface;

class YamlTransformer {

	protected $yaml_string;
	protected $yaml;
	protected $handlers = [];
	protected $subscriptions = [];
	public $setup_file;

	/**
	 * @param string $yaml_string contents of YAML configuration
	 */
	public function __construct($yaml_string) {
		$this->yaml_string = $yaml_string;
		// try to parse the string
		try {
			$this->yaml = Yaml::parse($yaml_string);
		} catch (ParseException $e) {
			printf('Unable to parse the YAML string: %s', $e->getMessage());
			return;
		}
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
	 * Subscribe to an event.
	 * @param  string   $event   type of event
	 * @param  callable $handler event handler
	 */
	public function subscribe($event, callable $handler) {
		if (!isset($this->subscriptions[$event])) {
			$this->subscriptions[$event] = [];
		} else {
			// unsubscribe to avoid duplicate handlers
			$this->unsubscribe($event, $handler);
		}
		$this->subscriptions[$event][] = $handler;
	}

	/**
	 * Unsubscribe from an event.
	 * @param  string   $event   type of event
	 * @param  callable $handler handler to remove
	 */
	public function unsubscribe($event, callable $handler) {
		if (!empty($this->subscriptions[$event])) {
			$this->subscriptions[$event] = array_diff($this->subscriptions[$event], [$handler]);
		}
	}

	/**
	 * Dispatch a given event and call all subscribed handlers.
	 * @param  string $event type of event
	 * @param  mixed  $value some optional starting value, if the handlers are expected to manipulate said value
	 * @param  mixed  $data  optional additional data that may be relevant to the handlers
	 * @return Event         the event object after all handlers have acted upon it
	 */
	public function dispatch($event, $value = null, $data = null) {
		$event_obj = new Event($this, $event, $value, $data);
		if (!empty($this->subscriptions[$event])) {
			foreach ($this->subscriptions[$event] as $handler) {
				call_user_func($handler, $event_obj);
			}
		}
		return $event_obj;
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
		$expression = $this->dispatch('pre_compile', $expression)->value;

		if ($expression instanceof CompilableInterface) {
			$compiled = $expression->compile($this);
		} else {
			$compiled = var_export($expression, true);
		}

		return $this->dispatch('post_compile', $compiled, ['expression' => $expression])->value;
	}

	/**
	 * Process the configuration file, calling all transform handlers.
	 * @return string  compiled file
	 */
	public function execute() {
		$this->dispatch('pre_execute');

		foreach ($this->yaml as $property => $data) {
			if (isset($this->handlers[$property])) {
				foreach ($this->handlers[$property] as $identifier => $handler) {
					call_user_func($handler, $this, $data);
				}
			} else {
				fwrite(STDERR, "Warning: no handler(s) for property '$property'\n");
			}
		}

		$compiled = $this->compile($this->setup_file);

		$this->dispatch('post_execute');
		return $compiled;
	}

}
