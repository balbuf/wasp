<?php

namespace OomphInc\WASP;

use Closure;

trait ServiceContainerTrait {

	protected $services = [];
	protected $serviceTypes = [];
	protected $serviceDefinitions = [];

	/**
	 * Handle any default values provided by the class definition.
	 * @param  array  $services default services to add
	 */
	protected function handleServiceDefaults(array $services = []) {
		foreach ($this->getDefaultServiceTypes() as $service => $type) {
			$this->setServiceType($service, $type);
		}

		foreach ($this->getDefaultServiceDefinitions() as $service => $definition) {
			$this->setServiceDefinition($service, $definition);
		}

		foreach ($services as $service => $object) {
			$this->setService($service, $object);
		}
	}

	protected function getDefaultServiceTypes() {
		return [];
	}

	protected function getDefaultServiceDefinitions() {
		return [];
	}

	/**
	 * Set the required object type for the particular service.
	 * @param string $service service
	 * @param string $type    class or interface
	 */
	public function setServiceType($service, $type) {
		$this->serviceTypes[$service] = $type;
	}

	/**
	 * Get the required object type for the particular service.
	 * @param  string $service service
	 */
	public function getServiceType($service) {
		if (isset($this->serviceTypes[$service])) {
			return $this->serviceTypes[$service];
		}
	}

	/**
	 * Set a lazy-loaded service definition.
	 * @param string $service service
	 * @param Closure $closure closure which returns
	 */
	public function setServiceDefinition($service, Closure $closure) {
		$this->serviceDefinitions[$service] = $closure;
	}

	/**
	 * Get a lazy-loaded service definition.
	 * @param  string $service service
	 */
	public function getServiceDefinition($service) {
		if (isset($this->serviceDefinitions[$service])) {
			return $this->serviceDefinitions[$service]->bindTo($this);
		}
	}

	/**
	 * Set a service object.
	 * @param string $service service
	 * @param object $object  service object
	 */
	public function setService($service, $object) {
		// check that the service is the correct type
		if (isset($this->serviceTypes[$service]) && !$object instanceof $this->serviceTypes[$service]) {
			throw new \InvalidArgumentException('Object is not of type: ' . $this->serviceTypes[$service]);
		}
		$this->services[$service] = $object;
	}

	/**
	 * Get the requested service (and create if necessary).
	 * @param  string $service the service type
	 * @return object          requested service
	 */
	public function getService($service) {
		if (isset($this->services[$service])) {
			return $this->services[$service];
		} else if ($closure = $this->getServiceDefinition($service)) {
			$object = $closure();
			$this->setService($service, $object);
			return $object;
		} else {
			throw new \RuntimeException('Requested service does not exist');
		}
	}
}
