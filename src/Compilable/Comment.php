<?php

namespace OomphInc\WASP\Compilable;

class Comment extends BaseCompilable {

	public $comment = '';

	public function compile() {
		$comment = (string) $this->comment;

		// single line?
		if (strpos($comment, "\n") === false) {
			return "//$comment\n";
		} else {
			return "/*\n$comment\n*/";
		}
	}

}
