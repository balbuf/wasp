<?php

namespace OomphInc\WASP\Property;

interface PropertyManipulatorInterface {

	/**
	 * Manipulate properties before transformations are processed.
	 * @param  PropertyTree $propertyTree
	 * @param  DocBlockFinder $docBlockFinder
	 */
	public function manipulateProperties($propertyTree, $docBlockFinder);

}
