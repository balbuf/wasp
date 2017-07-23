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

	public function get_property($property) {
		if (isset($this->yaml[$property])) {
			return $this->yaml[$property];
		}
	}

	public function compile($expression) {
		if ($expression instanceof CompilableInterface) {
			return $expression->compile($this);
		} else {
			return var_export($expression, true);
		}
	}

	public function execute() {
		foreach ($this->yaml as $property => $data) {
			if (isset($this->handlers[$property])) {
				foreach ($this->handlers[$property] as $identifier => $handler) {
					call_user_func($handler, $this, $data);
				}
			} else {
				echo "Warning: no handler(s) for property '$property'\n";
			}
		}
		return $this->compile($this->setup_file);
	}

}
