<?php

namespace OomphInc\WASP\Compilable;

class TranslatableTextExpression extends BaseCompilable {

	public $text = '';

	public function compile() {
		$compiled = $this->transformer->compile((string) $this->text);
		if ($domain = $this->transformer->getProperty('localization', 'text_domain')) {
			$compiled = '__( ' . $compiled . ', ' . $this->transformer->compile($domain) . ' )';
		}
		return $compiled;
	}

}
