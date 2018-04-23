<?php

namespace OomphInc\WASP\DocBlock;

interface DocBlockConsumerInterface {

	/**
	 * Handle docblocks using the DocBlockFinder.
	 * @param  DocBlockFinder $docBlockFinder
	 */
	public function handleDocBlocks($docBlockFinder);

}
