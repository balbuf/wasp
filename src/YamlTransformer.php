<?php
// register all the transform methods onto here

namespace OomphInc\FAST_WP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use OomphInc\FAST_WP\Compilable\SetupFile;
use OomphInc\FAST_WP\Compilable\CompilableInterface;

class YamlTransformer {

	protected $yaml_string;
	protected $yaml;
	protected $handlers = [];
	protected $subscriptions = [];
	public $setup_file;

	public function __construct($yaml_string) {
		$this->yaml_string = $yaml_string;
		try {
			$this->yaml = Yaml::parse($yaml_string);
		} catch (ParseException $e) {
			printf('Unable to parse the YAML string: %s', $e->getMessage());
			return;
		}
		$this->setup_file = new SetupFile();
	}

	public function add_handler($property, $identifier, callable $handler) {
		$this->handlers[$property][$identifier] = $handler;
	}

	public function remove_handler($property, $identifier) {
		unset($this->handlers[$property][$identifier]);
	}

	public function subscribe($event, callable $handler) {
		if (!isset($this->subscriptions[$event])) {
			$this->subscriptions[$event] = [];
		} else {
			// unsubscribe to avoid duplicate handlers
			$this->unsubscribe($event, $handler);
		}
		$this->subscriptions[$event][] = $handler;
	}

	public function unsubscribe($event, callable $handler) {
		if (!empty($this->subscriptions[$event])) {
			$this->subscriptions[$event] = array_diff($this->subscriptions[$event], [$handler]);
		}
	}

	public function dispatch($event, $value = null, $data = null) {
		$event_obj = new Event($this, $event, $value, $data);
		if (!empty($this->subscriptions[$event])) {
			foreach ($this->subscriptions[$event] as $handler) {
				call_user_func($handler, $event_obj);
			}
		}
		return $event_obj;
	}

	public function get_property($property) {
		if (isset($this->yaml[$property])) {
			return $this->yaml[$property];
		}
	}

	public function set_property($property, $value) {
		$this->yaml[$property] = $value;
	}

	public function compile($expression) {
		$expression = $this->dispatch('pre_compile', $expression)->value;

		if ($expression instanceof CompilableInterface) {
			$compiled = $expression->compile($this);
		} else {
			$compiled = var_export($expression, true);
		}

		return $this->dispatch('post_compile', $compiled, ['expression' => $expression])->value;
	}

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
