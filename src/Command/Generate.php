<?php

/**
 * Main command to generate a file from a YAML source.
 */

namespace OomphInc\WASP\Command;

use OomphInc\WASP\YamlTransformer;
use OomphInc\WASP\FileSystem\FileSystemHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use OomphInc\WASP\Event\Events;
use RuntimeException;

class Generate extends Command {

	public function __construct($wasp) {
		$this->wasp = $wasp;
		parent::__construct();
	}

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
		$filesystem = $this->wasp->getService('filesystem');

		if ($inputFile === '-') {
			$yamlString = $this->wasp->getService('stdin')->fetch();
		} else {
			$filesystem->pushd(null);
			$yamlString = $filesystem->readFile($inputFile);
			$filesystem->popd();
		}

		$transformer = new YamlTransformer($yamlString, $this->wasp->getService('dispatcher'), $this->wasp->getService('logger'));

		// resolve the root dir - first by seeing if explictly set
		if (!($rootDir = $input->getOption('root'))) {
			// can we discern from the output file path?
			if ($outputFile !== '-') {
				// get the setup file relative dir and normalize it
				$setupFileDir = implode(DIRECTORY_SEPARATOR, FileSystemHelper::getDirParts($transformer->getProperty('about', 'dir') ?: ''));
				// strip the setup file relative dir off the end of the output path to determine root dir
				$rootDir = preg_replace('#' . preg_quote($setupFileDir, '#') . '$#', '', dirname(realpath($outputFile)));
			// otherwise assume current working directory
			} else {
				$rootDir = getcwd();
			}
		}
		$transformer->setVar('rootDir', $rootDir);

		// fire off event to register transform handlers
		$event = new GenericEvent();
		$event->setArgument('transformer', $transformer);
		$this->wasp->getService('dispatcher')->dispatch(Events::REGISTER_TRANSFORMS, $event);

		$compiled = $transformer->execute($input->getOption('skip-handler'));

		// lint the file!
		if (!$input->getOption('no-lint')) {
			$this->wasp->getService('linter')->lint($compiled);
		}

		if ($outputFile === '-') {
			$output->write($compiled);
		} else {
			$filesystem->writeFile($outputFile, $compiled);
		}
	}
}
