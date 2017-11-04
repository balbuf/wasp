<?php

namespace OomphInc\WASP\Input;

use RuntimeException;

class StdIn implements StdInInterface {

	public function fetch() {
		$stdIn = file_get_contents('php://stdin');
		if ($stdIn === false) {
			throw new RuntimeException('Could not read from stdin');
		}
		return $stdIn;
	}

}
