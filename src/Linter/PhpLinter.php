<?php

namespace OomphInc\WASP\Linter;

use RuntimeException;

class PhpLinter implements LinterInterface {

	public function lint($code) {
		// open a process
		$process = proc_open('php -l', [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		], $pipes);

		if (!is_resource($process)) {
			throw new RuntimeException('Could not open process to lint compiled code');
		}

		fwrite($pipes[0], $code);
		fclose($pipes[0]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		fclose($pipes[1]); // close without reading, we don't care about it
		// successful lint?
		if (proc_close($process) !== 0) {
			throw new RuntimeException("Compiled code did not successfully lint:\n\n $err");
		}
	}

}
