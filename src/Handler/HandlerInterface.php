<?php

namespace OomphInc\WASP\Handler;

interface HandlerInterface {

	/**
	 * Get the properties that this handler is interested in.
	 * @return array  top-level property name(s)
	 */
	public function getSubscribedProperties();

	/**
	 * Get the unique identifier for this handler.
	 * @param  string $property top-level property that will be handled
	 * @return string           unique identifier
	 */
	public function getIdentifier($property);

	/**
	 * Get the default values for the property.
	 * @param  string $property top-level property
	 * @return array|callable|null         array of defaults, callable for late default handling, or null for no defaults
	 */
	public function getDefaults($property);

	/**
	 * Handle the property.
	 * @param YamlTransformer $transformer YAML transformer object
	 * @param  array  $config  config for this property
	 * @param  string $property  top-level property that is being handled
	 */
	public function handle($transformer, $config, $property);

}
