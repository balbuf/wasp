<?php
namespace OomphInc\FAST_WP;

use OomphInc\FAST_WP\Compilable\FunctionExpression;
use OomphInc\FAST_WP\Compilable\ArrayExpression;
use OomphInc\FAST_WP\Compilable\TranslatableTextExpression;

class BasicHandlers {

	public static function post_types($transformer, $data) {

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

		$transformer->setup_file->add_expression(new FunctionExpression('register_nav_menus', [new ArrayExpression($data)]));
	}

}
