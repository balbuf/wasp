<?php

namespace OomphInc\WASP\Property;

/**
 * Used to override the rendered value of the template.
 */
class PropertyOutput {

	protected $isOverriden = false;
	protected $output;

	/**
	 * Override the template's rendered output value with the provided value.
	 * @param  mixed $value override value
	 */
	public function setValue($value) {
		$this->isOverriden = true;
		$this->output = $value;
	}

	/**
	 * Has the output been overridden?
	 * @return boolean
	 */
	public function isOverridden() {
		return $this->isOverriden;
	}

	/**
	 * Get the overridden output value.
	 * @return mixed
	 */
	public function getValue() {
		return $this->output;
	}

	/**
	 * Reset the override state to not overridden.
	 */
	public function reset() {
		$this->isOverriden = false;
		$this->output = null;
	}

}
