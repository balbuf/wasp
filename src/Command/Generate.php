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

class Generate extends Command {

	protected function configure() {
		$this
			->setName('generate')
			->setDescription('Generate a file based off a YAML source.')
			->addArgument('input', InputArgument::REQUIRED, 'The input YAML file.')
			->addArgument('output', InputArgument::REQUIRED, 'The output compiled file.')
			->addOption('root', null, InputOption::VALUE_REQUIRED, 'The file root (default: current working directory).')
			->addOption('include', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Additional files to include before executing.')
			->addOption('lock_file', null, InputOption::VALUE_REQUIRED, 'Path to composer.lock file.')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$input_file = $input->getArgument('input');
		$output_file = $input->getArgument('output');

		if ($input_file === '-') {
			$input_file = 'php://stdin';
		}

		$yaml_string = file_get_contents($input_file);

		if ($yaml_string === false) {
			// @todo log a proper error
			$output->writeln("Error reading file $input_file");
			exit(1);
		}

		// optional files to include
		foreach ($input->getOption('include') as $file) {
			require_once $file;
		}

		$transformer = new YamlTransformer($yaml_string);

		foreach (get_class_methods('OomphInc\\WASP\\BasicHandlers') as $handler) {
			$transformer->add_handler($handler, 'wasp_' . $handler, ['OomphInc\\WASP\\BasicHandlers', $handler]);
		}

		$compiled = $transformer->execute();

		// open a process to lint the file!
		$process = proc_open('php -l', [
			0 => ['pipe', 'r'],
			1 => ['file', '/dev/null', 'w'], // suppress output
			2 => ['file', '/dev/null', 'w'],
		], $pipes);

		if (is_resource($process)) {
			fwrite($pipes[0], $compiled.'.');
			fclose($pipes[0]);
			// successful lint?
			if (proc_close($process) !== 0) {
				// @todo exit with error - syntax error
			}
		} else {
			// @todo exit with error - could not lint
		}

		if ($output_file === '-') {
			echo $compiled;
		} else {
			file_put_contents($output_file, $compiled);
		}
	}
}
