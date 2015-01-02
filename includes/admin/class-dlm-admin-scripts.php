<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Admin_Scripts {

	/**
	 * Setup hooks
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_scripts() {
		global $pagenow;

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {

			// Enqueue Edit Post JS
			wp_enqueue_script(
				'dlm_edit_post',
				plugins_url( '/assets/js/edit-post' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION
			);

			// Make JavaScript strings translatable
			wp_localize_script( 'dlm_edit_post', 'dlm_strings', $this->get_strings( 'edit-post' ) );

		}

		if ( 'edit.php' == $pagenow && isset( $_GET['page'] ) && 'download-monitor-settings' === $_GET['page'] ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_settings',
				plugins_url( '/assets/js/settings' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION
			);

		}



	}

	/**
	 * Get JS strings
	 *
	 * @param $file
	 *
	 * @return array
	 */
	private function get_strings( $file ) {
		switch ( $file ) {
			case 'edit-post':
				$strings = array(
					'insert_download' => __( 'Insert Download', 'download-monitor' )
				);
				break;
			default:
				$strings = array();
		}

		return $strings;
	}

}