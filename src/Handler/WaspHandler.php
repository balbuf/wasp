<?php

namespace OomphInc\WASP\Handler;

use OomphInc\WASP\Wasp;
use OomphInc\WASP\FileSystem\FileSystemHelper;

class WaspHandler implements HandlerInterface {

	// supported URL functions that can be used when specifying the url context
	const CONTEXT_TOKENS_REGEX = '/^%((?:home|site|admin|includes|content|plugins|theme)_url|get_(?:stylesheet|template)_directory_uri)%(.*)$/i';

	/**
	 * @inheritDoc
	 */
	public function getSubscribedProperties() {
		return [Wasp::META_PROPERTY];
	}

	/**
	 * @inheritDoc
	 */
	public function getIdentifier($property) {
		return 'wasp_meta';
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaults($property) {
		return [
			'url_context' => 'theme',
			'dir' => '',
			'docblock_match_files' => '/\.php$/i',
			'disabled_handlers' => [],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function handle($transformer, $config, $property) {
		$context = $config['url_context'];

		// use the context to construct a base url
		switch ($context) {
			case 'theme':
				$base = 'get_stylesheet_directory_uri() . DIRECTORY_SEPARATOR';
				break;

			case 'plugin':
				$base = 'plugins_url( __FILE__ )';
				// if the setup file is in a subdir of the file root, we need to strip off those dirs
				if ($depth = count(FileSystemHelper::getDirParts($config['dir']))) {
					$base = str_repeat('dirname( ', $depth) . $base . str_repeat(' )', $depth);
				}
				$base .= ' . DIRECTORY_SEPARATOR';
				break;

			default:
				$context = FileSystemHelper::trailingSlash($context);
				if (preg_match(static::CONTEXT_TOKENS_REGEX, $context, $matches)) {
					$base = strtolower($matches[1]) . '() . ' . var_export($matches[2], true);
				} else {
					$base = var_export($context, true);
				}
		}

		$transformer->outputExpression->addExpression(
			$transformer->create('RawExpression', [
				'expression' => "\$baseUrl = {$base};\n",
			]),
			['priority' => 2.5]
		);
	}

}
