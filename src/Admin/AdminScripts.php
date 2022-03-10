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
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'elementor_enqueue_scripts' ) );
	}

	/**
	 * Enqueue only elementor admin specific scripts
	 */
	public function elementor_enqueue_scripts(){
		$dlm = download_monitor();

		// Enqueue Edit Post JS
		wp_enqueue_script(
			'dlm_insert_download',
			plugins_url( '/assets/js/insert-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
			array( 'jquery' ),
			DLM_VERSION
		);

		// Make JavaScript strings translatable
		wp_localize_script( 'dlm_insert_download', 'dlm_id_strings', $this->get_strings( 'edit-post' ) );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_scripts() {
		global $pagenow, $post;

		$dlm = download_monitor();

		// Enqueue Edit Post JS
		wp_enqueue_script(
			'dlm_insert_download',
			plugins_url( '/assets/js/insert-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
			array( 'jquery' ),
			DLM_VERSION
		);

		// Notices JS
		wp_enqueue_script(
			'dlm_notices',
			plugins_url( '/assets/js/notices' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
			array( 'jquery' ),
			DLM_VERSION
		);

		// Make JavaScript strings translatable
		wp_localize_script( 'dlm_insert_download', 'dlm_id_strings', $this->get_strings( 'edit-post' ) );

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {

			// Enqueue Downloadable Files Metabox JS
			if (
				( $pagenow == 'post.php' && isset( $post ) && 'dlm_download' === $post->post_type )
				||
				( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && 'dlm_download' == $_GET['post_type'] )
			) {

				wp_enqueue_media(
					array(
						'post' => $post->ID,
					)
				);

				// Upload file JS
				wp_enqueue_script(
					'dlm_upload_file_js',
					plugins_url( '/assets/js/upload-file' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);
				wp_add_inline_script( 'dlm_upload_file_js', 'const max_file_size = ' . wp_max_upload_size() . ';', 'before' );

				// Enqueue Edit Download JS
				wp_enqueue_script(
					'dlm_edit_download',
					plugins_url( '/assets/js/edit-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);

				// Make JavaScript strings translatable
				wp_localize_script( 'dlm_edit_download', 'dlm_ed_strings', $this->get_strings( 'edit-download' ) );

			}

			// Enqueue Downloadable Files Metabox JS
			if (
				( $pagenow == 'post.php' && isset( $post ) && \Never5\DownloadMonitor\Shop\Util\PostType::KEY === $post->post_type )
				||
				( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && \Never5\DownloadMonitor\Shop\Util\PostType::KEY == $_GET['post_type'] )
			) {

				// Enqueue Select2
				wp_enqueue_script(
					'dlm_select2',
					plugins_url( '/assets/js/select2/select2.min.js', $dlm->get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);

				wp_enqueue_style( 'dlm_select2_css', download_monitor()->get_plugin_url() . '/assets/js/select2/select2.min.css' );

				// Enqueue Edit Product JS
				wp_enqueue_script(
					'dlm_edit_product',
					plugins_url( '/assets/js/shop/edit-product' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
					array( 'jquery', 'dlm_select2' ),
					DLM_VERSION
				);

				// Make JavaScript strings translatable
				wp_localize_script( 'dlm_edit_product', 'dlm_ep_strings', $this->get_strings( 'edit-product' ) );
			}

		}

		if ( 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && 'dlm_download' === $_GET['post_type'] && ! isset( $_GET['page'] ) ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_download_overview',
				plugins_url( '/assets/js/overview-download' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);
			// Make JavaScript strings translatable
			wp_localize_script(
				'dlm_download_overview',
				'dlm_download_overview',
				array(
					'copy_shortcode'    => esc_html__( 'Copy shortcode', 'download-monitor' ),
					'shortcode_copied' => esc_html__( 'Shortcode copied', 'download-monitor' ),
				)
			);

			// Enqueue Download Duplicator JS
			wp_enqueue_script(
				'dlm_download_duplicator',
				plugins_url( '/assets/js/download-duplicator' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file()),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);			

		}

		if ( 'edit.php' == $pagenow && isset( $_GET['page'] ) && 'download-monitor-reports' === $_GET['page'] ) {

			// Enqueue Reports JS
			wp_enqueue_script(
				'dlm_reports_chartjs',
				plugins_url( '/assets/js/reports/charts.min.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);

			wp_enqueue_script(
				'dlm_reports',
				plugins_url( '/assets/js/reports/reports.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);

			// Make JavaScript strings translatable
			wp_localize_script( 'dlm_reports', 'dlm_rs', $this->get_strings( 'reports' ) );

			wp_enqueue_script(
				'dlm_reports_date_range_selector',
				plugins_url( '/assets/js/reports/charts-date-range-selector' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION,
				true
			);

		}

		if ( 'edit.php' == $pagenow && isset( $_GET['page'] ) && ( 'download-monitor-settings' === $_GET['page'] || 'dlm-extensions' === $_GET['page'] ) ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_settings',
				plugins_url( '/assets/js/settings' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION
			);

			wp_localize_script( 'dlm_settings', 'dlm_settings_vars', array(
				'img_path'          => download_monitor()->get_plugin_url() . '/assets/images/',
				'lazy_select_nonce' => wp_create_nonce( 'dlm-settings-lazy-select-nonce' ),
				'settings_url'      => DLM_Admin_Settings::get_url()
			) );

		}

		// This should handle all extensions activation and deactivation single-handed
		if ( isset($_GET['page']) && 'dlm-installed-extensions' === $_GET['page'] ) {

			// Enqueue Extesions JS
			wp_enqueue_script(
				'dlm_extensions',
				plugins_url( '/assets/js/extensions' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $dlm->get_plugin_file() ),
				array( 'jquery' ),
				DLM_VERSION
			);
		}

		if ( 'options.php' == $pagenow && isset( $_GET['page'] ) && 'dlm_legacy_upgrade' === $_GET['page'] ) {

			// Enqueue Settings JS
			wp_enqueue_script(
				'dlm_legacy_upgrader',
				plugins_url( '/assets/js/legacy-upgrader/build/bundle.js', $dlm->get_plugin_file() ),
				array(),
				DLM_VERSION
			);

			wp_localize_script( 'dlm_legacy_upgrader', 'dlm_lu_vars', array(
				'nonce'       => wp_create_nonce( 'dlm_legacy_upgrade' ),
				'assets_path' => plugins_url( '/assets/js/legacy-upgrader/build/assets/', $dlm->get_plugin_file() )
			) );

			wp_enqueue_style( 'dlm_legacy_upgrader_css', download_monitor()->get_plugin_url() . '/assets/js/legacy-upgrader/build/style.css' );
		}

		do_action( 'dlm_admin_scripts_after' );

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
					'confirm_delete' => __( 'Are you sure you want to delete this file ? ', 'download-monitor' ),
					'browse_file'    => __( 'Browse for a file', 'download-monitor' ),
				);
				break;
			case 'reports':
				$strings = array(
					'ajax_nonce' => wp_create_nonce( 'dlm_reports_data' ),
					'img_path'   => download_monitor()->get_plugin_url() . '/assets/images/',
				);
				break;
			default:
				$strings = array();
		}

		return $strings;
	}

}