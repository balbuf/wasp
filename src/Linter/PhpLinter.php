<?php

namespace OomphInc\WASP\Linter;

class PhpLinter implements LinterInterface {

	public function lint($code) {
		// open a process
		$process = proc_open('php -l', [
			0 => ['pipe', 'r'],
			1 => ['file', '/dev/null', 'w'], // suppress output
			2 => ['pipe', 'w'],
		], $pipes);

		if (!is_resource($process)) {
			throw new RuntimeException('Could not open process to lint compiled code');
		}

		fwrite($pipes[0], $code);
		fclose($pipes[0]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		// successful lint?
		if (proc_close($process) !== 0) {
			throw new RuntimeException("Compiled code did not successfully lint:\n\n $err");
		}
	}

}
