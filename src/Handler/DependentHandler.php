<?php

namespace OomphInc\WASP\Handler;

use InvalidArgumentException;

/**
 * Handler that declares service dependencies.
 */
abstract class DependentHandler extends AbstractHandler {

	public function __construct(array $services = []) {
		$requested = static::getRequestedServices();
		foreach ($services as $service => $obj) {
			if (!in_array($service, $requested, true)) {
				continue;
			}

			$this->$service = $obj;
			$requested = array_diff($requested, [$service]);
		}

		// did we get all the requested services?
		if (count($requested)) {
			throw new InvalidArgumentException('Missing service(s): ' . implode(', ', $requested));
		}
	}

	public static function getRequestedServices() {
		return [];
	}

}
