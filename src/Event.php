<?php

namespace OomphInc\FAST_WP;

class Event {

	public $originator;
	public $type;
	public $value;
	public $data;

	public function __construct($originator, $type, $value = null, $data = null) {
		$this->originator = $originator;
		$this->type = $type;
		$this->value = $value;
		$this->data = $data;
	}

}
