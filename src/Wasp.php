<?php

namespace OomphInc\WASP;

use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use OomphInc\WASP\Input\PartialInputDefinition;
use Symfony\Component\Console\ConsoleEvents;
use OomphInc\WASP\Event\Events;
use Closure;

class Wasp {

	use ServiceContainerTrait;

	const NAME = 'wasp';
	const VERSION = 'beta';
	const COMPOSER_TYPE = 'wasp-plugin';
	const META_PROPERTY = 'wasp'; // top-level property where wasp-related settings are placed

	protected $application;
	protected $plugins = [];
	protected $globalInputDefinition;

	/**
	 * @param array  $services default services
	 * @param string $lockFile contents of composer lock file for the project
	 */
	public function __construct(array $services = [], $lockFile = null) {
		$this->handleServiceDefaults($services);

		// create the application
		$application = new Application(static::NAME, static::VERSION);
		// add global options
		$application->getDefinition()->addOptions($this->getGlobalOptions());
		// add commands
		$application->add(new Command\Generate($this));
		// do not auto exit upon command completion
		$application->setAutoExit(false);
		$this->setApplication($application);

		// bind input just to global params for early processing
		$input = $this->getService('input');
		$input->bind($this->getGlobalInputDefinition());
		// create a bound closure to call the protected method that sets up output verbosity and format
		$configureIO = Closure::bind(function($input, $output) {
			$this->configureIO($input, $output);
		}, $application, $application);
		$configureIO($input, $this->getService('output'));

		// optional files to include
		foreach ($input->getOption('include') as $file) {
			if (!is_readable($file)) {
				throw new RuntimeException("File does not exist or is not readable: $file");
			}
			require_once $file;
		}

		// parse lock file and initialize plugins
		if ($lockFile) {
			if ($input->getOption('no-plugins')) {
				$this->getService('logger')->debug('Skipping plugins due to --no-plugins flag');
			} else {
				$this->initializePluginsFromLock($lockFile, $input->getOption('disable-plugin'));
			}
		}

		$this->getService('dispatcher')->dispatch(Events::PLUGINS_LOADED);
	}

	/**
	 * Get the default types of services that may be accepted.
	 * @return array [service => class type, ...]
	 */
	protected function getDefaultServiceTypes() {
		return [
			'input' => 'Symfony\Component\Console\Input\InputInterface',
			'output' => 'Symfony\Component\Console\Output\OutputInterface',
			'logger' => 'Psr\Log\LoggerInterface',
			'dispatcher' => 'Symfony\Component\EventDispatcher\EventDispatcherInterface',
			'filesystem' => __NAMESPACE__ . '\FileSystem\FileSystemInterface',
			'stdin' => __NAMESPACE__ . '\Input\StdInInterface',
			'linter' => __NAMESPACE__ . '\Linter\LinterInterface',
		];
	}

	/**
	 * Get the default service definitions (closures which return an instance of the service).
	 * @return array [service => closure, ...]
	 */
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
	 * Get global options that can apply to any command.
	 * @return array InputOption and/or InputArgument objects
	 */
	protected function getGlobalOptions() {
		return [
			new InputOption('include', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Additional files to include before executing.'),
			new InputOption('disable-plugin', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Plugins to disable when executing commands.'),
			new InputOption('no-plugins', null, InputOption::VALUE_NONE, 'Disable all plugins when executing command.'),
		];
	}

	/**
	 * Get options that can be supplied for any command.
	 * @return array option objects
	 */
	public function getDefaultGlobalInputDefinition() {
		// merge application options with global options
		return new PartialInputDefinition(array_merge($this->getApplication()->getDefinition()->getOptions(), $this->getGlobalOptions()));
	}

	/**
	 * Get the global input definition object.
	 * @return OptionalInputDefinition
	 */
	public function getGlobalInputDefinition() {
		if (!$this->globalInputDefinition) {
			$this->setGlobalInputDefinition($this->getDefaultGlobalInputDefinition());
		}
		return $this->globalInputDefinition;
	}

	/**
	 * Set the global input definition object.
	 * @param OptionalInputDefinition $inputDefinition
	 */
	public function setGlobalInputDefinition(InputDefinition $inputDefinition) {
		$this->globalInputDefinition = $inputDefinition;
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
	 * @param string $lockFile  contents of composer lock file
	 * @param array  $disabled  slugs of plugins to disable
	 */
	protected function initializePluginsFromLock($lockFile, array $disabled = []) {
		$logger = $this->getService('logger');
		$lock = json_decode($lockFile, true);

		if (!is_array($lock)) {
			$logger->warning('Could not parse lock file');
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

				// disable plugin?
				if (in_array($package['name'], $disabled, true)) {
					$logger->debug("Plugin '{$package['name']}' is disabled");
					continue;
				}

				// class name is set?
				if (empty($package['extra']['class'])) {
					$logger->warning("Class name for '{$package['name']}' is not set");
					continue;
				}

				if (!class_exists($package['extra']['class'])) {
					$logger->warning("Specified class does not exist: {$package['extra']['class']}");
					continue;
				}

				// invoke the plugin!
				$plugin = $this->plugins[$package['name']] = new $package['extra']['class']($this);
				if ($plugin instanceof EventSubscriberInterface) {
					$this->getService('dispatcher')->addSubscriber($plugin);
				}
			}
		}
	}

	/**
	 * Get all instantiated plugins.
	 * @return array plugin objects
	 */
	public function getPlugins() {
		return $this->plugins;
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
