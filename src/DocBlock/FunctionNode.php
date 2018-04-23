<?php

namespace OomphInc\WASP\DocBlock;

use PhpParser\Node\Stmt\Function_;

class FunctionNode extends DocBlockSubject {

	protected $file;
	protected $node;
	protected $name;
	protected $fullName;

	/**
	 * @param FileNode   $file  parent file node
	 * @param Function_ $node  parsed node
	 */
	public function __construct(FileNode $file, Function_ $node) {
		$this->file = $file;
		$this->node = $node;
		$this->name = $node->name;
		$this->fullName = (string) $node->namespacedName;
		$this->setDocBlock($node->getDocComment());
	}

	/**
	 * Get parent file node.
	 * @return FileNode
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Get parsed node.
	 * @return Function_
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * Get function name (without namespace).
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get fully qualified named (with namespace).
	 * @return string
	 */
	public function getFullName() {
		return $this->fullName;
	}

	/**
	 * Get function parameters.
	 * @return array  PhpParser\Node\Param objects
	 */
	public function getParams() {
		return $this->node->getParams();
	}

	/**
	 * Whether the function returns by reference.
	 * @return boolean
	 */
	public function returnsByRef() {
		return $this->node->returnsByRef();
	}

}
