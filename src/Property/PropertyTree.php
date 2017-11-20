<?php

namespace OomphInc\WASP\Property;

use Twig_Environment;
use OomphInc\WASP\Wasp;
use RuntimeException;

class PropertyTree {

	const PROP_SELF = 'this'; // twig property to refer to itself (for self-referential user defaults)
	const PROP_SIBLINGS = 'this'; // twig property to reference sibling properties
	const PROP_ROOT = 'top'; // twig property to reference the root config
	const PROP_VARS = 'vars'; // twig property to reference vars set in the meta property
	const PROP_ASCEND = 'parent'; // twig pseudo property to ascend up one level in the property chain
	const PROP_DEFAULT = 'default'; // config property to provide user defaults
	const PROP_NAME = 'name'; // config property corresponding to key for assoc arrays that contain only assoc arrays

	protected $twig;
	protected $configRaw = []; // raw config as entered
	protected $config = []; // config processed with defaults
	protected $defaults = []; // handler-defined defaults
	protected $userDefaults = []; // user-defined defaults

	public function __construct(Twig_Environment $twig) {
		$this->setTwig($twig);
	}

	/**
	 * Set a property.
	 * @param string|array [$property...] property chain
	 * @param  mixed $value  the value to set
	 */
	public function set() {
		$chain = func_get_args();
		$value = array_pop($chain);
		$chain = static::unnestArray($chain);
		// set the raw values
		static::setInTree($this->configRaw, $chain, $value);
		$this->processDefaults($chain);
	}

	/**
	 * Get the raw value at the given property chain, if set.
	 * @param  string|array [$property...] property name
	 * @return mixed            config value, if set
	 */
	public function getRaw() {
		return static::getFromTree($this->configRaw, static::unnestArray(func_get_args()));
	}

	/**
	 * Get the processed value at the given property chain, if set.
	 * @param  string|array [$property...] property name
	 * @return mixed            processed config value, if set
	 */
	public function get() {
		$chain = static::unnestArray(func_get_args());
		return $this->processValue(static::getFromTree($this->config, $chain), $chain);
	}

	/**
	 * Process a property value, rendering twig templates.
	 * @param  mixed $value raw value
	 * @param  array $chain property chain leading up to this value for context
	 * @return mixed        processed value
	 */
	public function processValue($value, $chain = []) {
		// store the chains we are currently processing to detect circular references
		static $processing = [];
		static $circularReference = null;
		$serializedChain = serialize($chain);
		// check for circular references
		if (isset($processing[$serializedChain])) {
			// we cannot throw an error here because it would bubble up through a __toString, which is illegal
			// instead, we make a note, bail early, and throw at the end
			$circularReference = $chain;
			return $value;
		}
		$processing[$serializedChain] = true;

		// recursively process contents of array
		if (is_array($value)) {
			array_walk($value, function(&$item, $key) use ($chain) {
				$item = $this->processValue($item, array_merge($chain, [$key]));
			});
		} else if (is_string($value)) {
			// remove last item of the context chain so that "this" actually refers to the parent of this property
			$siblingChain = array_slice($chain, 0, -1);
			// process twig template values
			$value = $this->getTwig()->createTemplate($value)->render([
				static::PROP_SIBLINGS => new PropertyChain($this, $siblingChain, static::PROP_ASCEND),
			] + $this->createGlobalContext());

			// look for a user-defined default property, by replacing this prop's parent's name with "default"
			$userDefaultChain = $chain;
			array_splice($userDefaultChain, -2, 1, static::PROP_DEFAULT);

			// does a user value exist? if so, check to see if it is self-referential
			if (is_string($userDefault = $this->getUserDefault($userDefaultChain))) {
				// starting twig context
				$context = $this->createGlobalContext();

				// is siblings prop named something different than self prop?
				if (static::PROP_SIBLINGS !== static::PROP_SELF) {
					$context += [
						static::PROP_SIBLINGS => new PropertyChain($this, $siblingChain, static::PROP_ASCEND),
						static::PROP_SELF => new PropertyChain($this, $siblingChain),
					];
				} else {
					$context[static::PROP_SIBLINGS] = new PropertyChain($this, $siblingChain, static::PROP_ASCEND);
				}

				// when referencing itself, the twig value should be the current processed value we have
				$context[static::PROP_SELF]->setShortCircuitValue($siblingChain, $value);
				$newValue = $this->getTwig()->createTemplate($userDefault)->render($context);

				// did we reference ourself?
				if (in_array($siblingChain, $context[static::PROP_SELF]->getHistory(), true)) {
					// use this value
					$value = $newValue;
				}
			}
		}

		// we are done processing this chain
		unset($processing[$serializedChain]);
		// are we done recursing?
		if (!count($processing)) {
			// did we have a circular reference?
			if ($circularReference) {
				throw new RuntimeException('Circular reference in config property: ' . implode('.', $circularReference));
			}
			$circularReference = null;
		}

		return $value;
	}

	/**
	 * Context items that are provided for all twig rendering.
	 * @return array twig context array
	 */
	protected function createGlobalContext() {
		return [
			static::PROP_ROOT => new PropertyChain($this),
			static::PROP_VARS => new PropertyChain($this, [Wasp::META_PROPERTY, static::PROP_VARS]),
		];
	}

	/**
	 * Set the default value for a property.
	 * @param string|array [$property...] property chain
	 * @param  mixed $value  the default value to set
	 */
	public function setDefault() {
		$chain = func_get_args();
		$value = array_pop($chain);
		$chain = static::unnestArray($chain);
		static::setInTree($this->defaults, $chain, $value);
		// re-process defaults for the entire top-level property
		$this->processDefaults(array_slice($chain, 0, 1));
	}

	/**
	 * Get the default at the given property chain, if set.
	 * @param  string|array [$property...] property name
	 * @return mixed            default value, if set
	 */
	public function getDefault() {
		return static::getFromTree($this->defaults, static::unnestArray(func_get_args()));
	}

	/**
	 * Get the user default at the given property chain, if set.
	 * @param  string|array [$property...] property name
	 * @return mixed            default value, if set
	 */
	public function getUserDefault() {
		return static::getFromTree($this->userDefaults, static::unnestArray(func_get_args()));
	}

	/**
	 * Pluck out user defaults and place in the user defaults array.
	 * @param  mixed $value value to pluck from
	 * @param  array $chain property chain
	 * @return mixed        value with any defaults removed
	 */
	protected function pluckUserDefaults($value, $chain) {
		if (is_array($value)) {
			// pluck out user defaults
			if (static::isAssocArray($value) && isset($value[static::PROP_DEFAULT]) && static::hasOnlyAssocArrays($value)) {
				// set user default
				static::setInTree($this->userDefaults, array_merge($chain, [static::PROP_DEFAULT]), $value[static::PROP_DEFAULT]);
				unset($value[static::PROP_DEFAULT]);
			}
			// recursively pluck for all values
			array_walk($value, function(&$item, $key) use ($chain) {
				$item = $this->pluckUserDefaults($item, array_merge($chain, [$key]));
			});
		}
		return $value;
	}

	/**
	 * Process and save the raw values against the defaults for the given chain.
	 * @param  array $chain property chain
	 */
	public function processDefaults($chain) {
		$processed = $this->getRaw($chain);
		// pluck out user defaults
		$processed = $this->pluckUserDefaults($processed, $chain);
		// user defaults can have self-referential properties, but remove them as they are handled elsewhere
		$userDefaults = $this->removeSelfReferentialTemplates($this->getUserDefault($chain));
		// fill in defaults
		static::applyDefaults($processed, $userDefaults, $this->getDefault($chain));
		// set the processed values
		static::setInTree($this->config, $chain, $processed);
	}

	/**
	 * Remove self-referential templates from defaults, as these should not be filled in as actual values.
	 * @param  mixed $value value to remove templates from
	 * @return mixed value but without self-referential templates
	 */
	protected function removeSelfReferentialTemplates($value) {
		if (!static::isAssocArray($value)) {
			return $value;
		}

		// look for templates to remove
		foreach ($value as $key => $item) {
			// rescurse assoc array
			if (static::isAssocArray($item)) {
				$value[$key] = $this->removeSelfReferentialTemplates($item);
			// check strings only
			} else if (is_string($item)) {
				$test = new PropertyChain($this);
				$test->setShortCircuitValue([], '');
				$this->getTwig()->createTemplate($item)->render([static::PROP_SELF => $test]);
				// was "this" accessed?
				if (in_array([], $test->getHistory(), true)) {
					unset($value[$key]);
				}
			}
		}

		return $value;
	}

	/**
	 * Set the Twig environment object.
	 * @param Twig_Environment $twig twig environment
	 */
	public function setTwig(Twig_Environment $twig) {
		$this->twig = $twig;
	}

	/**
	 * Get the Twig environment.
	 * @return Twig_Environment
	 */
	public function getTwig() {
		return $this->twig;
	}

	/**
	 * Determine if an array is assoc or not.
	 * @param  mixed   $array value to test
	 * @return boolean        true if assoc, false if not
	 */
	public static function isAssocArray($array, $countEmpty = false) {
		if (!is_array($array)) {
			return false;
		}

		// do we want to count an empty array as assoc?
		if (!count($array) && $countEmpty) {
			return true;
		}

		$i = 0;
		foreach ($array as $key => $value) {
			if ($key !== $i++) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine if an array contains only assoc arrays.
	 * @param  array   $array array to test
	 * @return boolean        true if all items are assoc arrays
	 */
	public static function hasOnlyAssocArrays(array $array) {
		foreach ($array as $value) {
			if (!static::isAssocArray($value)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the value in the multidimensional array (tree) corresponding to the given property chain, if set.
	 * @param  array  $tree  multidimensional array
	 * @param  array  $chain property chain
	 * @return mixed       value, if set
	 */
	public static function getFromTree(array $tree, array $chain = []) {
		$value = $tree;
		foreach ($chain as $key) {
			if (is_array($value) && isset($value[$key])) {
				$value = $value[$key];
			} else {
				return;
			}
		}
		return $value;
	}

	/**
	 * Set a value in a multidimensional array at the corresponding property chain.
	 * @param array  &$tree multidimensial array
	 * @param array  $chain property chain
	 * @param mixed  $value value to set
	 */
	public static function setInTree(array &$tree, array $chain, $value) {
		if (count($chain) === 0) {
			$tree = $value;
			return;
		}

		$key = array_shift($chain);

		if (count($chain) === 0) {
			$tree[$key] = $value;
		} else {
			if (!isset($tree[$key]) || !is_array($tree[$key])) {
				$tree[$key] = [];
			}
			static::setInTree($tree[$key], $chain, $value);
		}
	}

	/**
	 * If the array has only one item and it is also an array, return that item, otherwise the array.
	 * @param  array  $array  possibly nested array
	 * @return array        possibly unnested array
	 */
	public static function unnestArray(array $array) {
		return count($array) === 1 && is_array($array[0]) ? $array[0] : $array;
	}

	/**
	 * Apply defaults to a given value.
	 * @param  mixed &$value value to apply defaults to
	 * @param  array [$defaults...] arrays of defaults to apply, in order of precedence
	 */
	public static function applyDefaults(&$value) {
		// we can only apply defaults on assoc arrays
		if (!static::isAssocArray($value)) {
			return;
		}

		// grab the defaults
		$defaults = array_slice(func_get_args(), 1);
		if (!count($defaults)) {
			return;
		}

		// is this an assoc array with only assoc arrays?
		if (static::hasOnlyAssocArrays($value)) {
			// look for ['default' => []] arrays within
			$defaultsArrs = [];
			foreach ($defaults as $default) {
				if (static::isAssocArray($default) // must be an assoc array
					&& count($default) === 1 // can only have one item
					&& isset($default[static::PROP_DEFAULT]) // that item's key must be "default"
					&& static::isAssocArray($default[static::PROP_DEFAULT], true) // that item must also be an assoc or empty array
				) {
					$defaultsArrs[] = $default[static::PROP_DEFAULT];
				}
			}

			// do we have any defaults arrays?
			if (count($defaultsArrs)) {
				array_walk($value, function(&$item, $key) use ($defaultsArrs) {
					// add a defaults array to fill in "name" property, using the assoc key
					$defaultsArrs[] = [static::PROP_NAME => $key];
					call_user_func_array([static::class, 'applyDefaults'], array_merge([&$item], $defaultsArrs));
				});
				// we are done with this one!
				return;
			}
		}

		// fill in defaults
		foreach ($defaults as $default) {
			// default must be an assoc array
			if (!static::isAssocArray($default)) {
				continue;
			}
			// cycle through and apply defaults
			foreach ($default as $key => $defaultValue) {
				// if the key isn't set, take the whole default value
				if (!isset($value[$key])) {
					$value[$key] = $defaultValue;
				// otherwise if value and default are both assoc arrays, recursively merge in values
				} else if (static::isAssocArray($value[$key]) && static::isAssocArray($defaultValue)) {
					static::applyDefaults($value[$key], $defaultValue);
				}
			}
		}
	}

}
