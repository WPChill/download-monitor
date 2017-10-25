<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Template_Handler {

	/**
	 * get_template_part method.
	 *
	 * @access public
	 *
	 * @param string $slug
	 * @param string $name (default: '')
	 * @param string $custom_dir
	 * @param array $args
	 *
	 * @return void
	 */
	public function get_template_part( $slug, $name = '', $custom_dir = '', $args = array() ) {
		$template = '';

		// The plugin path
		$plugin_path = download_monitor()->get_plugin_path();

		// Look in yourtheme/slug-name.php and yourtheme/download-monitor/slug-name.php
		if ( $name ) {
			$template = locate_template( array( "{$slug}-{$name}.php", "download-monitor/{$slug}-{$name}.php" ) );
		}

		// If a custom path was defined, check that next
		if ( ! $template && $custom_dir && file_exists( trailingslashit( $custom_dir ) . "{$slug}-{$name}.php" ) ) {
			$template = trailingslashit( $custom_dir ) . "{$slug}-{$name}.php";
		}

		// Get default slug-name.php
		if ( ! $template && $name && file_exists( $plugin_path . "/templates/{$slug}-{$name}.php" ) ) {
			$template = $plugin_path . "/templates/{$slug}-{$name}.php";
		}

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/download-monitor/slug.php
		if ( ! $template ) {
			$template = locate_template( array( "{$slug}.php", "download-monitor/{$slug}.php" ) );
		}

		// If a custom path was defined, check that next
		if ( ! $template && $custom_dir && file_exists( trailingslashit( $custom_dir ) . "{$slug}.php" ) ) {
			$template = trailingslashit( $custom_dir ) . "{$slug}.php";
		}

		// Get default slug-name.php
		if ( ! $template && file_exists( $plugin_path . "/templates/{$slug}.php" ) ) {
			$template = $plugin_path . "/templates/{$slug}.php";
		}

		// Allow 3rd party plugin filter template file from their plugin
		$template = apply_filters( 'dlm_get_template_part', $template, $slug, $name );

		// Load template if we've found one
		if ( $template ) {

			// Extract args if there are any
			if ( is_array( $args ) && count( $args ) > 0 ) {
				extract( $args );
			}

			do_action( 'dlm_before_template_part', $template, $slug, $name, $custom_dir, $args );

			include( $template );

			do_action( 'dlm_after_template_part', $template, $slug, $name, $custom_dir, $args );
			//load_template( $template, false );
		}
	}

}