<?php

namespace OomphInc\WASP\Handler;

use RuntimeException;
use OomphInc\WASP\Property\PropertyManipulatorInterface;

/**
 * Handler proxy allows a plugin to register many HandlerInterface objects
 * that will only be instantiated if its respective property needs to be handled.
 */
class HandlerProxy implements HandlerInterface, PropertyManipulatorInterface {

	protected $classes = [];
	protected $handlers = [];
	protected $wasp;
	protected $prefix;
	protected $hasManipulated = false;

	public function __construct($wasp, $prefix) {
		$this->wasp = $wasp;
		$this->prefix = $prefix;
	}

	/**
	 * Set a class name for a top-level property.
	 * @param string $property top-level property
	 * @param string $class    fully qualified class name
	 */
	public function setHandlerClass($property, $class) {
		$this->classes[$property] = $class;
	}

	/**
	 * Get the handler object for the given property, if available.
	 * @param  string $property top-level property
	 * @return HandlerInterface       handler object
	 */
	protected function getHandler($property) {
		if (isset($this->handlers[$property])) {
			return $this->handlers[$property];
		} else if (isset($this->classes[$property])) {
			// dependent handler?
			if (is_callable([$this->classes[$property], 'getRequestedServices'])) {
				// collect the handler's requested services
				$services = [];
				foreach (call_user_func([$this->classes[$property], 'getRequestedServices']) as $service) {
					$services[$service] = $this->wasp->getService($service);
				}
				$handler = new $this->classes[$property]($services);
			} else {
				$handler = new $this->classes[$property];
			}
			return $this->handlers[$property] = $handler;
		}
		throw new RuntimeException("Handler for requested property '{$property}' is not registered!");
	}

	/**
	 * @inheritDoc
	 */
	public function getSubscribedProperties() {
		return array_keys($this->classes);
	}

	/**
	 * @inheritDoc
	 */
	public function getIdentifier($property) {
		return $this->prefix . $property;
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaults($property) {
		return $this->getHandler($property)->getDefaults($property);
	}

	/**
	 * @inheritDoc
	 */
	public function handle($transformer, $config, $property) {
		$this->getHandler($property)->handle($transformer, $config, $property);
	}

	/**
	 * @inheritDoc
	 */
	public function manipulateProperties($propertyTree, $docBlockFinder) {
		// make sure we only run the manipulators once
		if ($this->hasManipulated) {
			return false;
		}

		foreach ($this->classes as $property => $class) {
			if (!in_array('OomphInc\WASP\Property\PropertyManipulatorInterface', class_implements($class))) {
				continue;
			}

			$this->getHandler($property)->manipulateProperties($propertyTree, $docBlockFinder);
		}

		$this->hasManipulated = true;
	}

}
