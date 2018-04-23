<?php

namespace OomphInc\WASP\DocBlock;

use OomphInc\WASP\FileSystem\FileSystemInterface;
use Psr\Log\LoggerInterface;
use PhpParser\Parser;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use RuntimeException;

class DocBlockFinder {

	const FILES = 'files';
	const FUNCTIONS = 'functions';
	const CLASSES = 'classes';
	const METHODS = 'methods';

	protected $hasSearched = false;
	protected $filePattern;
	protected $filesystem;
	protected $parser;
	protected $traverser;
	protected $files = [];

	/**
	 * @param FileSystemInterface $filesystem
	 * @param LoggerInterface     $logger
	 * @param Parser              $parser
	 * @param NodeTraverser       $traverser
	 */
	public function __construct(FileSystemInterface $filesystem, LoggerInterface $logger, Parser $parser, NodeTraverser $traverser) {
		$this->filesystem = $filesystem;
		$this->logger = $logger;
		$this->parser = $parser;
		$this->traverser = $traverser;
	}

	/**
	 * Set the file search pattern.
	 * @param string $filePattern
	 */
	public function setFilePattern($filePattern) {
		$this->filePattern = $filePattern;
		$this->reset();
	}

	/**
	 * Using the filepattern, search and parse files.
	 */
	public function execute() {
		if (!isset($this->filePattern)) {
			throw new RuntimeException('No file pattern set');
		}

		$files = $this->filesystem->getFiles($this->filePattern, FileSystemInterface::RECURSIVE | FileSystemInterface::ONLY_FILES);

		foreach ($files as $file) {
			try {
				$code = $this->filesystem->readFile($file);
			} catch (RuntimeException $e) {
				$this->logger->warn("Could not read file '$file' - skipping");
				continue;
			}

			$nodes = $this->traverser->traverse($this->parser->parse($code));
			$this->files[] = new FileNode($file, $nodes);
		}

		$this->hasSearched = true;
	}

	/**
	 * Find nodes matching the given conditions.
	 * @param  string $type  node type
	 * @param  array  $where conditions
	 * @return array        nodes
	 */
	public function find($type, $where) {
		if (!$this->hasSearched()) {
			$this->execute();
		}

		$return = [];

		foreach ($this->files as $file) {
			// handle by type
			switch ($type) {
				case static::FILES:
					if ($file->docBlockMatches($where)) {
						$return[] = $file;
					}
					break;

				case static::FUNCTIONS:
					foreach ($file->getFunctions() as $function) {
						if ($function->docBlockMatches($where)) {
							$return[] = $function;
						}
					}
					break;

				case static::CLASSES:
					foreach ($file->getClasses() as $class) {
						if ($class->docBlockMatches($where)) {
							$return[] = $class;
						}
					}
					break;

				case static::METHODS:
					foreach ($file->getClasses() as $class) {
						foreach ($class->getMethods() as $method) {
							if ($method->docBlockMatches($where)) {
								$return[] = $method;
							}
						}
					}
					break;
			}
		}

		return $return;
	}

	/**
	 * Get the top-level file nodes.
	 * @return array  file nodes
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * Has the search been executed?
	 * @return boolean
	 */
	public function hasSearched() {
		return $this->hasSearched;
	}

	/**
	 * Reset the finder to the pre-searched state.
	 */
	public function reset() {
		$this->hasSearched = false;
		$this->files = [];
	}

	/**
	 * Debug helper function to print nodes.
	 * @param  array $nodes parsed nodes
	 */
	public function printNodes($nodes) {
		foreach ($nodes as $node) {
			echo get_class($node) . "\n";

			if ($node instanceof FileNode) {
				echo $node->getFileName() . "\n";
				print_r($node->getTags());

				$this->printNodes($node->getFunctions());
				$this->printNodes($node->getClasses());
			} else if ($node instanceof FunctionNode) {
				echo $node->getFullName() . "\n";
				print_r($node->getTags());
			} else if ($node instanceof ClassNode) {
				echo $node->getFullName() . "\n";
				print_r($node->getTags());

				$this->printNodes($node->getMethods());
			} else if ($node instanceof MethodNode) {
				echo $node->getName() . "\n";
				print_r($node->getTags());
			}
		}
	}

}
