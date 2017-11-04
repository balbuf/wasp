<?php

namespace OomphInc\WASP;

use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Wasp {

	use ServiceContainerTrait;

	const NAME = 'wasp';
	const VERSION = 'beta';
	const COMPOSER_TYPE = 'wasp-plugin';

	protected $application;
	protected $plugins = [];

	public function __construct(array $services = []) {
		$this->handleServiceDefaults($services);
		$application = new Application(static::NAME, static::VERSION);
		$application->add(new Command\Generate($this));
		$application->setAutoExit(false);
		$this->setApplication($application);
	}

	protected function getDefaultServiceTypes() {
		return [
			'input' => 'Symfony\Component\Console\Input\InputInterface',
			'output' => 'Symfony\Component\Console\Output\OutputInterface',
			'logger' => 'Psr\Log\LoggerInterface',
			'dispatcher' => 'Symfony\Component\EventDispatcher\EventDispatcherInterface',
			'filesystem' => __NAMESPACE__ . '\\FileSystem\\FileSystemInterface',
			'stdin' => __NAMESPACE__ . '\\Input\\StdInInterface',
			'linter' => __NAMESPACE__ . '\\Linter\\LinterInterface',
		];
	}

	protected function getDefaultServiceDefinitions() {
		return [
			'logger' => function() {
				return new ConsoleLogger($this->getService('output'));
			},
			'dispatcher' => function() {
				return new EventDispatcher();
			},
			'filesystem' => function() {
				return new FileSystem\FileSystem();
			},
			'stdin' => function() {
				return new Input\StdIn();
			},
			'linter' => function() {
				return new Linter\PhpLinter();
			},
		];
	}

	/**
	 * Set the console application object.
	 * @param Application $application application object
	 */
	public function setApplication(Application $application) {
		$this->application = $application;
	}

	/**
	 * Get the console application object.
	 * @return Application application object
	 */
	public function getApplication() {
		return $this->application;
	}

	/**
	 * Parse lockfile and initialize any wasp plugins.
	 * @param  string $lock composer lock file
	 */
	public function initializePlugins($lock) {
		$lock = json_decode($lock, true);

		if (!is_array($lock)) {
			$this->getService('logger')->warning('Could not read lock file');
			return;
		}

		foreach (['packages', 'packages-dev'] as $key) {
			if (empty($lock[$key]) || !is_array($lock[$key])) {
				continue;
			}

			// check each package to see if it is a wasp plugin
			foreach ($lock[$key] as $package) {
				if (!isset($package['type']) || $package['type'] !== static::COMPOSER_TYPE) {
					continue;
				}

				// class name is set?
				if (empty($package['extra']['class'])) {
					$this->getService('logger')->warning("Class name for {$package['name']} is not set");
					continue;
				}

				if (!class_exists($package['extra']['class'])) {
					$this->getService('logger')->warning("Specified class does not exist: {$package['extra']['class']}");
					continue;
				}

				// invoke the plugin!
				$plugin = $this->plugins[] = new $package['extra']['class']($this);
				if ($plugin instanceof EventSubscriberInterface) {
					$this->getService('dispatcher')->addSubscriber($plugin);
				}
			}
		}
	}

	/**
	 * Run the application.
	 * @return int exit code
	 */
	public function run() {
		$application = $this->getApplication();
		$application->setDispatcher($this->getService('dispatcher'));
		return $application->run($this->getService('input'), $this->getService('output'));
	}

}
