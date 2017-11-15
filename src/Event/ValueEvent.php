<?php

namespace OomphInc\WASP\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

class ValueEvent extends GenericEvent {

	protected $value;

	public function __construct($value) {
		$this->setValue($value);
		parent::__construct();
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

}
