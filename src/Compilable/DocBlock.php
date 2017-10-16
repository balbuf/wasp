<?php

namespace OomphInc\WASP\Compilable;

class DocBlock extends BaseCompilable {

	public $lines = [];
	public $lineLength = 80;

	public function compile() {
		$compiled = "/**\n";

		// render the lines
		foreach ($this->lines as $line) {
			$compiled .= ' * ' . wordwrap($line, $this->lineLength - 3, "\n * ") . "\n";
		}

		return $compiled . " */";
	}

}
