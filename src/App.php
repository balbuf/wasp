<?php

namespace OomphInc\WASP;

use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class App {

	static public function create($input, $output, $logger, $filesystem = null, $dispatcher = null) {
		$dispatcher = $dispatcher ?: new EventDispatcher();
		$application = new Application('wasp', 'beta');
		$filesystem = $filesystem ?: new FileSystemHelper($application, '');
		$application->plugins = [];
		$application->services = (object) [
			'dispatcher' => $dispatcher,
			'input' => $input,
			'output' => $output,
			'logger' => $logger,
			'filesystem' => $filesystem,
		];
		$application->setDispatcher($dispatcher);
		$application->add(new Command\Generate());
		return $application;
	}

	static public function setPlugins($application, $lock) {
		$lock = json_decode($lock, true);

		if (empty($lock)) {
			$application->services->logger->warning('Could not read lock file');
			return;
		}

		foreach (['packages', 'packages-dev'] as $key) {
			if (empty($lock[$key]) || !is_array($lock[$key])) {
				continue;
			}

			// check each package to see if it is a wasp plugin
			foreach ($lock[$key] as $package) {
				if (!isset($package['type']) || $package['type'] !== WASP_TYPE) {
					continue;
				}

				// class name is set?
				if (empty($package['extra']['class'])) {
					$application->services->logger->warning("Class name for {$package['name']} is not set");
					continue;
				}

				if (!class_exists($package['extra']['class'])) {
					$application->services->logger->warning("Specified class does not exist: {$package['extra']['class']}");
					continue;
				}

				// invoke the plugin!
				$plugin = $application->plugins[] = new $package['extra']['class']($application);
				if ($plugin instanceof EventSubscriberInterface) {
					$application->services->dispatcher->addSubscriber($plugin);
				}
			}
		}
	}

	static public function run($application) {
		$application->run($application->services->input, $application->services->output);
	}

}
