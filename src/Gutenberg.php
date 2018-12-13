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

		// register the block in PHP
		register_block_type( 'download-monitor/download-button', array(
//			'style' => 'gutenberg-examples-03-esnext',
//			'editor_style' => 'gutenberg-examples-03-esnext-editor',
			'editor_script' => 'dlm_gutenberg_blocks',
		) );

		wp_set_script_translations( 'dlm_gutenberg_blocks', 'download-monitor', plugin_dir_path( DLM_PLUGIN_FILE ) . 'languages' );

		wp_localize_script( 'dlm_gutenberg_blocks', 'dlmBlocks', array(
			'ajax_getDownloads' => DLM_Ajax_Manager::get_ajax_url( 'get_downloads' ),
			'ajax_getVersions'  => DLM_Ajax_Manager::get_ajax_url( 'get_versions' ),
		) );


	}
}