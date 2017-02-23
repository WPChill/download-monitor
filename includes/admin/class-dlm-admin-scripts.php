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
		global $pagenow, $post;

		// Enqueue Edit Post JS
		wp_enqueue_script(
			'dlm_insert_download',
			plugins_url( '/assets/js/insert-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
			array( 'jquery' ),
			DLM_VERSION
		);

		// Notices JS
		wp_enqueue_script(
			'dlm_notices',
			plugins_url( '/assets/js/notices' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
			array( 'jquery' ),
			DLM_VERSION
		);

		// Make JavaScript strings translatable
		wp_localize_script( 'dlm_insert_download', 'dlm_id_strings', $this->get_strings( 'edit-post' ) );

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {

			// Enqueue Downloadable Files Metabox JS
			if ( ( $pagenow == 'post.php' && isset( $post ) && 'dlm_download' === $post->post_type ) || ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && 'dlm_download' == $_GET['post_type'] ) ) {

				// Enqueue Edit Download JS
				wp_enqueue_script(
					'dlm_edit_download',
					plugins_url( '/assets/js/edit-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);

				// Make JavaScript strings translatable
				wp_localize_script( 'dlm_edit_download', 'dlm_ed_strings', $this->get_strings( 'edit-download' ) );
			}


		}

		if ( 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && 'dlm_download' === $_GET['post_type'] && ! isset( $_GET['page'] ) ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_download_overview',
				plugins_url( '/assets/js/overview-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);

		}

		if ( 'edit.php' == $pagenow && isset( $_GET['page'] ) && ( 'download-monitor-settings' === $_GET['page'] || 'dlm-extensions' === $_GET['page'] ) ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_settings',
				plugins_url( '/assets/js/settings' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION
			);


			if ( 'dlm-extensions' === $_GET['page'] ) {
				// Enqueue Extesions JS
				wp_enqueue_script(
					'dlm_extensions',
					plugins_url( '/assets/js/extensions' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', WP_DLM::get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);
			}

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
			case 'edit-download':
				$strings = array(
					'confirm_delete' => __( 'Are you sure you want to delete this file ? ', 'download - monitor' ),
					'browse_file'    => __( 'Browse for a file', 'download - monitor' ),
				);
				break;
			default:
				$strings = array();
		}

		return $strings;
	}

}