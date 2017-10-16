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
			->addOption('no-lint', null, InputOption::VALUE_NONE, 'Suppress the linting step after generating the file.')
			->addOption('skip-handler', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Handlers to disable when generating the file.')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$inputFile = $input->getArgument('input');
		$outputFile = $input->getArgument('output');
		$application = $this->getApplication();

		if ($inputFile === '-') {
			$inputFile = 'php://stdin';
		}

		$yamlString = file_get_contents($inputFile);

		if ($yamlString === false) {
			throw new RuntimeException("Could not read file $inputFile");
		}

		$transformer = new YamlTransformer($yamlString, $application);
		$event = new GenericEvent();
		$event->setArgument('transformer', $transformer);
		$application->services->dispatcher->dispatch(Events::REGISTER_TRANSFORMS, $event);

		$compiled = $transformer->execute($input->getOption('skip-handler'));

		// lint the file!
		if (!$input->getOption('no-lint')) {
			// open a process
			$process = proc_open('php -l', [
				0 => ['pipe', 'r'],
				1 => ['file', '/dev/null', 'w'], // suppress output
				2 => ['pipe', 'w'],
			], $pipes);

			if (is_resource($process)) {
				fwrite($pipes[0], $compiled);
				fclose($pipes[0]);
				$err = stream_get_contents($pipes[2]);
				fclose($pipes[2]);
				// successful lint?
				if (proc_close($process) !== 0) {
					throw new RuntimeException("Compiled code did not successfully lint:\n\n $err");
				}
			} else {
				throw new RuntimeException('Could not open process to lint compiled code');
			}
		}

		if ($outputFile === '-') {
			echo $compiled;
		} else {
			if (file_put_contents($outputFile, $compiled) === false) {
				throw new RuntimeException("Could not write to file $outputFile");
			}
		}
	}
}
