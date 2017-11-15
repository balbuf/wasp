<?php

namespace OomphInc\WASP\Input;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * InputDefinition that only defines a subset of all the arguments that may be passed,
 * i.e. ignores passed options or arguments that do not exist.
 */
class PartialInputDefinition extends InputDefinition {

	/**
	 * No-ops to true as all options are considered valid.
	 */
	public function hasOption($name) {
		return true;
	}

	/**
	 * Gets or creates a generic option with the given name.
	 */
	public function getOption($name) {
		if (array_key_exists($name, $this->getOptions())) {
			return parent::getOption($name);
		} else {
			return new InputOption($name, null, InputOption::VALUE_OPTIONAL);
		}
	}

	/**
	 * No-ops to true as all arguments are considered valid.
	 */
	public function hasArgument($name) {
		return true;
	}

	/**
	 * Gets or creates a generic argument with the given name.
	 */
	public function getArgument($name) {
		if (array_key_exists($name, $this->getArguments())) {
			return parent::getArgument($name);
		} else {
			return new InputArgument($name, null, InputArgument::OPTIONAL);
		}
	}

	/**
	 * No-ops to true as all shortcuts are considered valid.
	 */
	public function hasShortcut($name) {
		return true;
	}

	/**
	 * Gets the associated long option or creates a generic option with the shortcut name.
	 */
	public function getOptionForShortcut($shortcut) {
		try {
			return parent::getOptionForShortcut($shortcut);
		} catch (\InvalidArgumentException $e) {
			return new InputOption($shortcut, null, InputOption::VALUE_OPTIONAL);
		}
	}

}
