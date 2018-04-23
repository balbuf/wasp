<?php

namespace OomphInc\WASP\DocBlock;

use PhpParser\Node\Stmt\Class_;

class ClassNode extends DocBlockSubject {

	protected $file;
	protected $node;
	protected $name;
	protected $fullName;
	protected $methods = [];

	/**
	 * @param FileNode $file  parent file node
	 * @param Class_ $node  parsed class node
	 */
	public function __construct(FileNode $file, Class_ $node) {
		$this->file = $file;
		$this->node = $node;
		$this->name = $node->name;
		$this->fullName = (string) $node->namespacedName;
		$this->setDocBlock($node->getDocComment());

		// pull out the class methods
		foreach ($node->getMethods() as $method) {
			$this->methods[] = new MethodNode($this, $method);
		}
	}

	/**
	 * Get the parent file node.
	 * @return FileNode
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Get the underlying parsed node.
	 * @return Class_
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * Get the class name (without namespace).
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the fully qualified class name (with namespace).
	 * @return string
	 */
	public function getFullName() {
		return $this->fullName;
	}

	/**
	 * Get the method nodes.
	 * @return array  MethodNode objects
	 */
	public function getMethods() {
		return $this->methods;
	}

	/**
	 * Whether the class is abstract.
	 * @return boolean
	 */
	public function isAbstract() {
		return $this->node->isAbstract();
	}

	/**
	 * Whether the class is final.
	 * @return boolean
	 */
	public function isFinal() {
		return $this->node->isFinal();
	}

	/**
	 * Get the name of the class which this class is extended from.
	 * @return PhpParser\Node\Name
	 */
	public function getExtends() {
		return $this->node->extends;
	}

	/**
	 * Get implemented interfaces.
	 * @return array  PhpParser\Node\Name objects
	 */
	public function getImplements() {
		return $this->node->implements;
	}

}
