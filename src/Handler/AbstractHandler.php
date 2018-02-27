<?php

namespace OomphInc\WASP\Handler;

abstract class AbstractHandler implements HandlerInterface {

	/**
	 * @inheritDoc
	 */
	public function getSubscribedProperties() {
		$classParts = explode('\\', static::class);
		// take only class name (with no namespace) and convert from camel to snake case
		return [strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', end($classParts)))];
	}

	/**
	 * @inheritDoc
	 */
	public function getIdentifier($property) {
		return strtolower(str_replace('\\', '_', static::class));
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaults($property) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	abstract public function handle($transformer, $config, $property);

}
