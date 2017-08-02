<?php

namespace OomphInc\WASP\Compilable;

class Comment implements CompilableInterface {

	public $comment;

	public function __construct($comment) {
		$this->comment = $comment;
	}

	public function compile($transformer) {
		$comment = (string) $this->comment;

		// single line?
		if (strpos($comment, "\n") === false) {
			return "//$comment\n";
		} else {
			return "/*\n$comment\n*/";
		}
	}

}
