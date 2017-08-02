<?php

namespace OomphInc\WASP\Compilable;

class TranslatableTextExpression implements CompilableInterface {

	public function __construct($text) {
		$this->text = $text;
	}

	public function compile($transformer) {
		$compiled = var_export((string) $this->text, true);
		if ($domain = $transformer->get_property('text_domain')) {
			$compiled = '__( ' . $compiled . ', ' . var_export($domain, true) . ' )';
		}
		return $compiled;
	}

}
