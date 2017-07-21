<?php

namespace Oomph\YAWC\ConfigFile;

use Oomph\YAWC\ExpressionInterface;

class ConfigFile {

	protected static $conditional = '';
	protected $config;
	protected $config_string;
	protected $config_file;

	public function __construct( string $config_file ) {
		$this->config_string = '';
		$this->config_file = $config_file;
	}
	//hook to place this code inside of a particular action, optional priority for that action
	public function add_action_hook_expression( ExpressionInterface $exp, string $hook, int $priority = 10, bool $lazy = false) {
		if ( $lazy ) {
			$this->config['lazy_expressions'][$hook][$priority][] = $exp;
		} else {
			$this->config['expressions'][$hook][$priority][] = $exp;
		}
	}
	
	// optional priority to determine order of expression (lower means sooner) (thank goodness for sparse arrays)
	public function add_bare_expression( ExpressionInterface $exp, int $priority = null ) {
		if ( $priority === null ) {
			$this->config['bare_expressions'][] = $exp;
		} else {
			// Sets config['bare_expressions'][$priority] = $exp
			array_splice( $this->config['bare_expressions'] , $priority, 0 , $exp );
		}
	}

	private function compile( string $key ) {
		if ( empty( $config['key'] ) ) {
			return;
		}

		foreach ( $this->config[$key] as $hook => $priorities ) {
			foreach ( $priorities as $priority => $expressions ) {
				$output .= "add_action( '$hook', function() {\n";
				foreach ( $expressions as $expression ) {
					$output .= $expression->compile();
				}
				$output .= "}, $priority );\n";
			}
		}
	}

	public function __toString() {
		$this->config_string .= array_reduce( $this->config['bare_expressions'], function( $str, $exp ) {
			return $str .= $exp->compile();
		}, '' ); 
		$this->compile( 'expressions' );
		if ( !empty( $this->conditional ) ) {
			$this->config_string .= "if ( {$this->conditional}() ) {\n";
			$this->compile( 'lazy_expressions' );
			$this->config_string .= "}\n";
		}

		return $this->config_string;
	}

	public function write_config() {
		if ( file_put_contents( (string) $this, $this->$config_file ) === false ) {
			throw new \RuntimeException( "Unable to write to file $config_file." );
		}
	}

}
