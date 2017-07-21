#!/usr/bin/env php
<?php 

require_once __DIR__ . '/../vendor/autoload.php';

use Oomph\YAWC\ConfigFile;
use Oomph\YAWC\YamlTransformer;
use Oomph\YAWC\BasicHandlers;

//get input (or env variables)
$theme_root = getenv( 'THEME_ROOT' );
$config_input = getenv( 'CONFIG_INPUT' );
$config_output = getenv( 'CONFIG_OUTPUT' );

$config_file = new ConfigFile( $config_output );
$transformer = new YamlTransformer( $config_file, $config_input );

foreach ( get_class_methods( 'BasicHandlers') as $handler ) {
	$transformer->add_handler( $handler, "BasicHandlers::$handler" );
}

$transformer->parse();
$config_file->write_string();

//beautify?
