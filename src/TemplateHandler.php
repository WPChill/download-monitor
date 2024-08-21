<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Template_Handler {

	/**
	 * Returns available templates for download button
	 *
	 * @return array
	 */
	public function get_available_templates() {
		return apply_filters( 'dlm_available_templates', array(
			''             => __( 'Default - Title and count', 'download-monitor' ),
			'button'       => __( 'Button - CSS styled button showing title and count', 'download-monitor' ),
			'box'          => __( 'Box - Box showing thumbnail, title, count, filename and filesize.', 'download-monitor' ),
			'filename'     => __( 'Filename - Filename and download count', 'download-monitor' ),
			'title'        => __( 'Title - Shows download title only', 'download-monitor' ),
			'version-list' => __( 'Version list - Lists all download versions in an unordered list', 'download-monitor' ),
			'custom'       => __( 'Custom template', 'download-monitor' ),
		) );
	}

	/**
	 * get_template_part method.
	 *
	 * @access public
	 *
	 * @param  string  $slug
	 * @param  string  $name  (default: '')
	 * @param  string  $custom_dir
	 * @param  array   $args
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
		$template = apply_filters( 'dlm_get_template_part', $template, $slug, $name, $args );

		// Allow 3rd party plugin filter template arguments from their plugin
		$args = apply_filters( 'dlm_get_template_part_args', $args, $template, $slug, $name );

		// Load template if we've found one
		if ( $template ) {
			// Extract args if there are any
			if ( is_array( $args ) && count( $args ) > 0 ) {
				extract( $args );
			}

			// Compatibility between extensions and templates.
			if ( ! isset( $download ) && isset( $dlm_download ) ) {
				$download = $dlm_download;
			}

			if ( ! isset( $dlm_download ) && isset( $download ) ) {
				$dlm_download = $download;
			}

			// Check if $dlm_download is set, if not set it to false. This happens to shortcodes where the Download is not set.
			if ( ! isset( $dlm_download ) ) {
				$dlm_download = false;
			}

			if ( ! isset( $download ) ) {
				$download = false;
			}

			if ( ! isset( $dlm_download ) ) {
				$dlm_download = false;
			}

			$attributes = $this->get_template_attributes( $download, $template );

			do_action( 'dlm_before_template_part', $template, $slug, $name, $custom_dir, $args );
			$attributes = $this->get_template_attributes( $dlm_download, $template, $slug, $name );
			include( $template );

			do_action( 'dlm_after_template_part', $template, $slug, $name, $custom_dir, $args );
			//load_template( $template, false );
		}
	}

	/**
	 * Get the template attributes
	 *
	 * @access public
	 *
	 * @param  object          $download  The download object.
	 * @param  string|boolean  $template  The template to be used.
	 *
	 * @return array
	 * @since  4.9.6
	 */
	private function get_template_attributes( $download, $template = false, $slug = 'content-download', $name = '' ) {
		if ( ! $download ) {
			return array();
		}
		$title = '';
		if ( $download->get_version()->has_version_number() ) {
			$title = sprintf( esc_html__( 'Version %s', 'download-monitor' ), esc_html( $download->get_version()->get_version_number() ) );
		}

		$default_attributes = array(
			'link_attributes' => array(
				'data-e-Disable-Page-Transition' => 'true',
				'class'                          => array( 'download-link' ),
				'title'                          => $title,
				'href'                           => $download->get_the_download_link(),
				'rel'                            => 'nofollow',
				'id'                             => 'download-link-' . $download->get_id(),
			),
		);

		return apply_filters( 'dlm_template_attributes', $default_attributes, $download, $name );
	}
}
