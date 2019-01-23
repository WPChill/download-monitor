<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Gutenberg {

	public function setup() {

		add_action( 'init', array( $this, 'load' ) );

	}

	public function load() {

		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		// register Gutenberg JS
		wp_register_script(
			'dlm_gutenberg_blocks',
			plugins_url( '/assets/blocks/dist/blocks.build.js', download_monitor()->get_plugin_file() ),
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
			DLM_VERSION
		);

		wp_register_style(
			'dlm_gutenberg_blocks-editor',
			plugins_url( '/assets/css/gb-editor.css', download_monitor()->get_plugin_file() ),
			array( 'wp-edit-blocks' ),
			DLM_VERSION
		);

		// register the block in PHP
		register_block_type( 'download-monitor/download-button', array(
//			'style' => 'gutenberg-examples-03-esnext',
			'editor_style'  => 'dlm_gutenberg_blocks-editor',
			'editor_script' => 'dlm_gutenberg_blocks',
		) );

		wp_set_script_translations( 'dlm_gutenberg_blocks', 'download-monitor', plugin_dir_path( DLM_PLUGIN_FILE ) . 'languages' );

		$templates = array( array( 'value' => 'settings', 'label' => __( 'Default from settings', 'download-monitor' ) ) );
		foreach ( download_monitor()->service( 'template_handler' )->get_available_templates() as $template_key => $template_value ) {
			$templates[] = array( 'value' => $template_key, 'label' => $template_value );
		}

		wp_localize_script( 'dlm_gutenberg_blocks', 'dlmBlocks', array(
			'ajax_getDownloads' => DLM_Ajax_Manager::get_ajax_url( 'get_downloads' ),
			'ajax_getVersions'  => DLM_Ajax_Manager::get_ajax_url( 'get_versions' ),
			'urlButtonPreview'  => add_query_arg( array(
				'dlm_gutenberg_download_preview' => '1',
			), site_url( '/', 'admin' ) ),
			'templates'         => json_encode( $templates )
		) );


	}
}