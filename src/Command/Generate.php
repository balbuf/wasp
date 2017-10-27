<?php

/**
 * Main command to generate a file from a YAML source.
 */

namespace OomphInc\WASP\Command;

use OomphInc\WASP\YamlTransformer;
use OomphInc\WASP\FileSystemHelper;
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
			->addOption('root', null, InputOption::VALUE_REQUIRED, 'The file root (default: discerned from output file path or current working directory).')
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

		$yamlString = $application->services->filesystem->readFile($inputFile, false);

		if ($yamlString === false) {
			throw new RuntimeException("Could not read file $inputFile");
		}

		$transformer = new YamlTransformer($yamlString, $application);

		// resolve the root dir - first by seeing if explictly set
		if (!($rootDir = $input->getOption('root'))) {
			// can we discern from the output file path?
			if ($outputFile !== '-') {
				// get the setup file relative dir and normalize it
				$setupFileDir = implode('/', FileSystemHelper::getDirParts($transformer->getProperty('about', 'dir') ?: ''));
				// strip the setup file relative dir off the end of the output path to determine root dir
				$rootDir = preg_replace('#' . preg_quote($setupFileDir, '#') . '$#', '', dirname(realpath($outputFile)));
			// otherwise assume current working directory
			} else {
				$rootDir = getcwd();
			}
		}
		$application->services->filesystem->setRootDir($rootDir);

		// fire off event to register transform handlers
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
			if ($application->services->filesystem->writeFile($outputFile, false, $compiled) === false) {
				throw new RuntimeException("Could not write to file $outputFile");
			}
		}
	}
}
