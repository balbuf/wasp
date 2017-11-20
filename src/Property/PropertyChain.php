<?php

namespace OomphInc\WASP\Property;

class PropertyChain {

	protected $propertyTree;
	protected $startingChain;
	protected $chain;
	protected $history = [];
	protected $shortCircuitValues = [];
	protected $ascendProp;

	public function __construct($propertyTree, $chain = [], $ascendProp = null) {
		$this->propertyTree = $propertyTree;
		$this->startingChain = $this->chain = $chain;
		$this->ascendProp = $ascendProp;
	}

	/**
	 * Any offset can be accessed, but if it doesn't exist we just return null.
	 */
	public function __isset($offset) {
		return true;
	}

	/**
	 * Add to the property chain (or remove in the case of ascendProp being set).
	 * @param  string $offset property name or value of ascendProp to go up a level
	 * @return $this
	 */
	public function __get($offset) {
		if (isset($this->ascendProp) && $offset === $this->ascendProp) {
			array_pop($this->chain);
		} else {
			$this->chain[] = $offset;
		}
		return $this;
	}

	/**
	 * Add to the chain and return the current value.
	 * @param  string $method property name
	 * @return mixed         value
	 */
	public function __call($method, $args) {
		$this->__get($method);
		return $this->getValue();
	}

	/**
	 * Set a value to be returned for the given chain, short-circuiting the property tree.
	 * @param array $chain property chain
	 * @param mixed $value value to return
	 */
	public function setShortCircuitValue($chain, $value) {
		$this->shortCircuitValues[serialize($chain)] = $value;
	}

	/**
	 * Get the value at the current property chain and reset the chain.
	 * @return mixed value
	 */
	public function getValue() {
		$serializedChain = serialize($this->chain);
		// check for value in short-circuit array, otherwise get from property tree
		$value = isset($this->shortCircuitValues[$serializedChain])
			? $this->shortCircuitValues[$serializedChain]
			: $this->propertyTree->get($this->chain);
		$this->resetChain();
		return $value;
	}

	/**
	 * Get the value at the current property chain as a string.
	 * @return string value
	 */
	public function __toString() {
		return (string) $this->getValue();
	}

	/**
	 * Reset the chain back to the starting chain and add the current chain to the access history.
	 */
	protected function resetChain() {
		$this->history[] = $this->chain;
		$this->chain = $this->startingChain;
	}

	/**
	 * Get the access history.
	 * @return array  all chains that were accessed
	 */
	public function getHistory() {
		return $this->history;
	}

}
