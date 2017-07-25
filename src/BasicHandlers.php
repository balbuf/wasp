<?php
namespace OomphInc\FAST_WP;

use OomphInc\FAST_WP\Compilable\FunctionExpression;
use OomphInc\FAST_WP\Compilable\ArrayExpression;
use OomphInc\FAST_WP\Compilable\TranslatableTextExpression;
use OomphInc\FAST_WP\Compilable\RawExpression;
use OomphInc\FAST_WP\Compilable\CompositeExpression;


class BasicHandlers {
	public static function fast_wp($transformer, $data) {
		foreach ($data as $property => $value) {
			$transformer->set_property($property, $value);
		}
	}

	public static function post_types($transformer, $data) {
		$defaults = [
			'labels' => [
				'name' => '%plural%',
				'all_items' => 'All %plural%',
				'add_new_item' => 'Add New %singular%',
				'edit_item' => 'Edit %singular%',
				'new_item' => 'New %singular%',
				'view_item' => 'View %singular%',
				'search_items' => 'Search %plural%',
				'not_found' => 'No %plural% found',
			],
			'show_ui' => true,
			'public' => true,
			'has_archive' => true,
			'show_in_nav_menus' => true,
			'menu_position' => 20,
			'map_meta_cap' => true,
			'supports' => [
				'title',
				'editor',
				'thumbnail',
			],
			'hierarchical' => false,
		];
		$patterns = ['%singular%', '%plural%'];
		if (isset($data['default'])) {
			$defaults = array_merge_recursive($defaults, $data['default']);
			unset($data['default']);
		}
		foreach ($data as $post_type => $args) {
			if (isset($args['post_type'])) {
				$post_type = $args['post_type'];
				unset($args['post_type']);
			}
			if (isset($args['label'])) {
				$plural = $args['label'];
			} elseif (isset($args['labels']['name'])) {
				$plural = $args['labels']['name'];
			} else {
				$plural = ucwords($post_type);
				$args['label'] = $plural;
			}
			if (!isset($args['labels']['singular_name'])) {
				if (substr($plural, -1) === 's') {
					$args['labels']['singular_name'] = substr($plural, 0, -1);
				} else {
					echo "Could not determine singular name for $post_type post type. Skipping.\n";
					continue;
				}
			}
			$args = array_merge_recursive($defaults, $args);
			$replacements = [$args['labels']['singular_name'], $plural];
			$args['labels'] = new ArrayExpression(array_map(function($label) {
				return new TranslatableTextExpression($label);
			}, str_replace($patterns, $replacements, $args['labels'])));
			$transformer->setup_file->add_expression(new FunctionExpression('register_post_type', [$post_type, new ArrayExpression($args)]), 'init');
		}

	}

	public static function taxonomies($transformer, $data) {

	}

	public static function site_options($transformer, $data) {
		foreach ($data as $option => $value) {
			$transformer->setup_file->add_lazy_expression(new FunctionExpression('update_option', [$option, $value]));
		}
	}

	public static function scripts($transformer, $data) {

	}

	public static function styles($transformer, $data) {

	}

	public static function image_sizes($transformer, $data) {
		foreach ($data as $name => $settings) {
			if (!isset($settings['width'], $settings['height'])) {
				echo "Error: missing width or height for image size '$name'\n";
				continue;
			}
			$settings += ['crop' => true];
			$args = [$name, $settings['width'], $settings['height'], $settings['crop']];
			$transformer->setup_file->add_expression(new FunctionExpression('add_image_size', $args), 'after_setup_theme');
		}
	}

	public static function constants($transformer, $data) {
		foreach ($data as $constant => $value) {
			$transformer->setup_file->add_expression(new FunctionExpression('define', [$constant, $value]));
		}
	}

	public static function menu_locations($transformer, $data) {
		$data = array_map(function($label) {
			return new TranslatableTextExpression($label);
		}, $data);

		$transformer->setup_file->add_expression(new FunctionExpression('register_nav_menus', [new ArrayExpression($data)]), 'after_setup_theme');
	}

	public static function autoloader($transformer, $data) {
		if (!isset($data['namespace'])) {
			echo "Error: no namespace set for autoloader property\n";
			return;
		}

		$data += ['dir' => 'src'];
		$autoloader = <<<'PHP'
/**
 * PSR 4 Autoloader for class includes.
 * @source  http://www.php-fig.org/psr/psr-4/examples/
 */
spl_autoload_register( function ( $class ) {
	// project-specific namespace prefix
	$prefix = %PREFIX%;
	// base directory for the namespace prefix
	$base_dir = __DIR__ . %DIR%;
	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}
	// get the relative class name
	$relative_class = substr( $class, $len );
	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
	// if the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
PHP;
		$autoloader = str_replace('%PREFIX%', var_export((string) $data['namespace'], true), $autoloader);
		$autoloader = str_replace('%DIR%', var_export('/' . trim((string) $data['dir'], '/') . '/', true), $autoloader);

		$transformer->setup_file->add_expression(new RawExpression($autoloader));
	}

}
