<?php

namespace OomphInc\WASP\Property;

/**
 * Simple object used to set and retrive values while processing twig templates.
 */
class PropertyEnv {

	public function set($property, $value) {
		$this->$property = $value;
	}

}
