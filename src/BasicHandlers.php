<?php
namespace OomphInc\FAST_WP;

use OomphInc\FAST_WP\Compilable\RawExpression;
use OomphInc\FAST_WP\Compilable\FunctionExpression;

class BasicHandlers {

	public static function post_types($transformer, $data) {
		$transformer->setup_file->add_lazy_expression(new RawExpression('//foo'));
	}

	public static function taxonomies($transformer, $data) {

	}

	public static function site_options($transformer, $data) {

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

	}

}
