<?php

/**
 * Main command to generate a file from a YAML source.
 */

namespace OomphInc\WASP\Command;

use OomphInc\WASP\YamlTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use OomphInc\WASP\Events;
use RuntimeException;

class Generate extends Command {

	protected function configure() {
		$this
			->setName('generate')
			->setDescription('Generate a file based off a YAML source.')
			->addArgument('input', InputArgument::REQUIRED, 'The input YAML file.')
			->addArgument('output', InputArgument::REQUIRED, 'The output compiled file.')
			->addOption('root', null, InputOption::VALUE_REQUIRED, 'The file root (default: current working directory).')
			->addOption('include', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Additional files to include before executing.')
			->addOption('no-lint', null, InputOption::VALUE_NONE, 'Suppress the linting step after generating the file')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$input_file = $input->getArgument('input');
		$output_file = $input->getArgument('output');
		$application = $this->getApplication();

		if ($input_file === '-') {
			$input_file = 'php://stdin';
		}

		$yaml_string = file_get_contents($input_file);

		if ($yaml_string === false) {
			throw new RuntimeException("Could not read file $input_file");
		}

		// optional files to include
		foreach ($input->getOption('include') as $file) {
			require_once $file;
		}

		$transformer = new YamlTransformer($yaml_string, $application);
		$event = new GenericEvent();
		$event->setArgument('transformer', $transformer);
		$application->services->dispatcher->dispatch(Events::REGISTER_TRANSFORMS, $event);

		$compiled = $transformer->execute();

		// lint the file!
		if (!$input->getOption('no-lint')) {
			// open a process
			$process = proc_open('php -l', [
				0 => ['pipe', 'r'],
				1 => ['file', '/dev/null', 'w'], // suppress output
				2 => ['file', '/dev/null', 'w'],
			], $pipes);

			if (is_resource($process)) {
				fwrite($pipes[0], $compiled);
				fclose($pipes[0]);
				// successful lint?
				if (proc_close($process) !== 0) {
					throw new RuntimeException('Compiled code did not successfully lint');
				}
			} else {
				throw new RuntimeException('Could not open process to lint compiled code');
			}
		}

		if ($output_file === '-') {
			echo $compiled;
		} else {
			if (file_put_contents($output_file, $compiled) === false) {
				throw new RuntimeException("Could not write to file $output_file");
			}
		}
	}
}
