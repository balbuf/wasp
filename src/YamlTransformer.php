<?php
// register all the transform methods onto here

namespace OomphInc\FAST_WP;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlTransformer {

	protected $handlers = [];
	protected $config_file;
	protected $raw_yaml;

	public function __construct(ConfigFile $config_file, $input_file) {
		$this->config_file = $config_file;
		$this->raw_yaml = file_get_contents( $input_file );
		if ( $this->raw_yaml === false ) {
			throw new \RuntimeException( "Unable to open config file: $input_file." );
		}
	}

	public function add_handler($property, $identifier, callable $handler) {
		$this->handlers[$identifier][$property] = $handler;
	}

	protected function parse( $input_file ) {
		// parse file, call any registered handlers for each top-level yaml prop
		// pass each handler ($configFile, $data)
		try {
			$parsed_yaml = Yaml::parse( $yaml );
		} catch ( ParseException $e ) {
			printf( "Unable to parse the YAML string: %s", $e->getMessage() );
		}

		foreach ( $handlers as $property => $handler ) {
			$handler( $this->config_file, $parsed_yaml[$property] );
		}
	}

}
