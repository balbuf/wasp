<?php

namespace OomphInc\WASP\DocBlock;

use PhpParser\Node\Stmt\ClassMethod;

class MethodNode extends DocBlockSubject {

	protected $class;
	protected $node;
	protected $name;

	/**
	 * @param ClassNode   $class parent class node
	 * @param ClassMethod $node  parsed node
	 */
	public function __construct(ClassNode $class, ClassMethod $node) {
		$this->class = $class;
		$this->node = $node;
		$this->name = $node->name;
		$this->setDocBlock($node->getDocComment());
	}

	/**
	 * Get parent class node.
	 * @return ClassNode
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * Get the parsed node.
	 * @return ClassMethod
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * Get the method name.
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Whether the method returns by reference.
	 * @return boolean
	 */
	public function returnsByRef() {
		return $this->node->returnsByRef();
	}

	/**
	 * Get method parameters.
	 * @return array  PhpParser\Node\Param objects
	 */
	public function getParams() {
		return $this->node->getParams();
	}

	/**
	 * Whether the method is public.
	 * @return boolean
	 */
	public function isPublic() {
		return $this->node->isPublic();
	}

	/**
	 * Whether the method is protected.
	 * @return boolean
	 */
	public function isProtected() {
		return $this->node->isProtected();
	}

	/**
	 * Whether the method is private.
	 * @return boolean
	 */
	public function isPrivate() {
		return $this->node->isPrivate();
	}

	/**
	 * Whether the method is abstract.
	 * @return boolean
	 */
	public function isAbstract() {
		return $this->node->isAbstract();
	}

	/**
	 * Whether the method is final.
	 * @return boolean
	 */
	public function isFinal() {
		return $this->node->isFinal();
	}

	/**
	 * Whether the method is static.
	 * @return boolean
	 */
	public function isStatic() {
		return $this->node->isStatic();
	}

}
