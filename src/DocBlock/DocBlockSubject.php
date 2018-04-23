<?php

namespace OomphInc\WASP\DocBlock;

abstract class DocBlockSubject {

	// docblock parsing pattern
	const DOCBLOCK_PATTERN = '/^\s*\*\s+@([^\s]+)(?:[ \t]+(.*))?$/m';

	protected $docBlock;
	protected $tags = [];

	/**
	 * Set the docblock and parse into tags.
	 * @param string $docBlock raw docblock
	 */
	protected function setDocBlock($docBlock) {
		$this->docBlock = $docBlock;
		if (!preg_match_all(static::DOCBLOCK_PATTERN, $docBlock, $matches, PREG_SET_ORDER)) {
			return;
		}

		// add the tags
		foreach ($matches as $match) {
			$this->tags[$match[1]][] = !empty($match[2]) ? trim($match[2]) : '';
		}
	}

	/**
	 * Whether the node's tags matches the given conditions.
	 * @param  array  $conditions array of condition arrays (multiple condition arrays will be considered an 'and')
	 *
	 *    has => tag
	 *    notHas => tag
	 *    equals => [tag => value]
	 *    notEquals => [tag => value]
	 *    matches => [tag => pattern]
	 *    notMatches => [tag => pattern]
	 *    and => [conditions...]
	 *    or => [conditions...]
	 *
	 * @return boolean       whether the node matches the conditions
	 */
	public function docBlockMatches(array $conditions) {
		foreach ($conditions as $condition) {
			reset($condition);
			$type = key($condition);
			$condition = reset($condition);

			switch ($type) {
				case 'and':
					if (!$this->docBlockMatches($condition)) {
						return false;
					}
					break;

				case 'or':
					$any = false;

					foreach ($condition as $subCondition) {
						if ($this->docBlockMatches([$subCondition])) {
							$any = true;
							break;
						}
					}

					if (!$any) {
						return false;
					}

					break;

				case 'has':
					if (!$this->getTag($condition)) {
						return false;
					}
					break;

				case 'notHas':
					if ($this->getTag($condition)) {
						return false;
					}
					break;

				case 'equals':
				case 'notEquals':
				case 'matches':
				case 'notMatches':
					// does the docblock have a tag that matches?
					$has = false;
					$test = reset($condition);
					$tag = key($condition);

					foreach ($this->getTag($tag) ?: [] as $value) {
						if ($type === 'equals' || $type === 'notEquals') {
							if ($value === $test) {
								$has = true;
							}
						} else {
							if (preg_match($test, $value)) {
								$has = true;
							}
						}
					}

					if (substr($type, 0, 3) === 'not') {
						if ($has) {
							return false;
						}
					} else {
						if (!$has) {
							return false;
						}
					}

					break;
			}
		}
		return true;
	}

	/**
	 * Get the value(s) of a tag.
	 * @param  string $tag tag name
	 * @return array|null   array of tag values, or null if none
	 */
	public function getTag($tag) {
		if (isset($this->tags[$tag])) {
			return $this->tags[$tag];
		}
	}

	/**
	 * Get all tags.
	 * @return array
	 */
	public function getTags() {
		return $this->tags;
	}

}
