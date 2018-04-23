<?php

namespace OomphInc\WASP\DocBlock;

use PhpParser\Comment\Doc;

class FileNode extends DocBlockSubject {

	protected $fileName;
	protected $nodes;
	protected $functions = [];
	protected $classes = [];

	/**
	 * @param string $fileName path to file
	 * @param array $nodes    parsed nodes
	 */
	public function __construct($fileName, array $nodes) {
		$this->fileName = $fileName;
		$this->nodes = $nodes;

		if (empty($nodes)) {
			return;
		}

		// the file doc block (if present) is attached to the first node
		// take the first doc comment type
		foreach ($nodes[0]->getAttribute('comments') ?: [] as $comment) {
			if ($comment instanceof Doc) {
				$this->setDocBlock($comment->getText());
				break;
			}
		}

		$this->processNodes($nodes);
	}

	/**
	 * Recursively process nodes.
	 * @param  array  $nodes  parsed nodes
	 */
	protected function processNodes(array $nodes) {
		foreach ($nodes as $node) {
			// we only care about classes and functions
			switch ($node->getType()) {
				case 'Stmt_Class':
					// cannot reference an anonymous class
					if ($node->isAnonymous()) {
						continue;
					}

					$this->classes[] = new ClassNode($this, $node);
					break;

				case 'Stmt_Function':
					$this->functions[] = new FunctionNode($this, $node);
					break;

				default:
					if (!empty($node->stmts)) {
						$this->processNodes($node->stmts);
					}

			}
		}
	}

	/**
	 * Get the path to the file.
	 * @return string
	 */
	public function getFileName() {
		return $this->fileName;
	}

	/**
	 * Get function nodes.
	 * @return array  nodes
	 */
	public function getFunctions() {
		return $this->functions;
	}

	/**
	 * Get class nodes.
	 * @return array  nodes
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * Get raw parsed nodes.
	 * @return array parsed nodes
	 */
	public function getNodes() {
		return $this->nodes;
	}

}
