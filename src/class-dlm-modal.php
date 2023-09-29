<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Modal class.
 *
 * Class that handles the No Access Modal and what it implies.
 *
 * @since 4.8.11
 */
class DLM_Modal {

	/**
	 * Holds the class object.
	 *
	 * @since 4.8.11
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Holds the enqueued scripts.
	 *
	 * @since 4.8.11
	 *
	 * @var array
	 */
	public static $enqueued_scripts;

	/**
	 * __construct function.
	 *
	 * @since 4.8.11
	 */
	private function __construct() {

		add_action( 'wp_ajax_nopriv_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_print_scripts', array( $this, 'inspect_scripts' ) );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Modal object.
	 * @since 4.8.11
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Modal ) ) {
			self::$instance = new DLM_Modal();
		}

		return self::$instance;

	}

	/**
	 * Set scripts
	 *
	 * @return void
	 * @since 4.8.11
	 */
	public function inspect_scripts() {
		global $wp_scripts;
		self::$enqueued_scripts = $wp_scripts->queue;
	}

	/**
	 * Display the XHR no access modal
	 *
	 * @return void
	 * @since 4.8.11 - moved from AjaxHandle.php
	 */
	public function xhr_no_access_modal() {

		$settings = download_monitor()->service( 'settings' );
		if ( ! isset( $_POST['download_id'] ) || ! isset( $_POST['version_id'] ) ) {
			if ( '1' === $settings->get_option( 'xsendfile_enabled' ) ) {
				wp_send_json_error( 'Missing download_id or version_id. X-Sendfile is enabled, so this is a problem.' );
			}
			wp_send_json_error( 'Missing download_id or version_id' );
		}

		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		// Action to allow the adition of extra scripts and code related to the shortcode.
		do_action( 'dlm_dlm_no_access_shortcode_scripts' );

		$atts           = array(
			'show_message' => 'true',
		);
		$content        = '';
		$no_access_page = $settings->get_option( 'no_access_page' );
		if ( ! $no_access_page ) {
			ob_start();

			// template handler.
			$template_handler = new DLM_Template_Handler();

			if ( 'empty-download' === $_POST['download_id'] || ( isset( $_POST['modal_text'] ) && ! empty( $_POST['modal_text'] ) ) ) {
				if ( isset( $_POST['modal_text'] ) && ! empty( $_POST['modal_text'] ) ) {
					echo sanitize_text_field( wp_unslash( $_POST['modal_text'] ) );
				} else {
					echo '<p>' . __( 'You do not have permission to download this file.', 'download-monitor' ) . '</p>';
				}
			} else {

				try {
					/** @var \DLM_Download $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $_POST['download_id'] ) );
					$version  = ( 'empty-download' !== $_POST['download_id'] ) ? download_monitor()->service( 'version_repository' )->retrieve_single( absint( $_POST['version_id'] ) ) : $download->get_version();
					$download->set_version( $version );

					// load no access template.
					$template_handler->get_template_part(
						'no-access',
						'',
						'',
						array(
							'download'          => $download,
							'no_access_message' => ( ( $atts['show_message'] ) ? wp_kses_post( get_option( 'dlm_no_access_error', '' ) ) : '' )
						)
					);
				} catch ( Exception $exception ) {
					wp_send_json_error( 'No download found' );
				}
			}

			$content = ob_get_clean();
		} else {
			$content = do_shortcode( apply_filters( 'the_content', get_post_field( 'post_content', $no_access_page ) ) );
			if ( '' === trim( $content ) ) {
				if ( isset( $_POST['modal_text'] ) && ! empty( $_POST['modal_text'] ) ) {
					$content = sanitize_text_field( wp_unslash( $_POST['modal_text'] ) );
				} else {
					$content = '<p>' . __( 'You do not have permission to download this file.', 'download-monitor' ) . '</p>';
				}
			}
		}

		$restriction_type = isset( $_POST['restriction'] ) && 'restriction-empty' !== $_POST['restriction'] ? sanitize_text_field( wp_unslash( $_POST['restriction'] ) ) : 'no_access_page';

		$title = apply_filters(
			'dlm_modal_title',
			array(
				'no_file_path'   => __( 'Error!', 'download-monitor' ),
				'no_file_paths'  => __( 'Error!', 'download-monitor' ),
				'access_denied'  => __( 'No access!', 'download-monitor' ),
				'file_not_found' => __( 'Error!', 'download-monitor' ),
				'not_found'      => __( 'Error!', 'download-monitor' ),
				'filetype'       => __( 'No access!', 'download-monitor' ),
				'no_access_page' => __( 'No access!', 'download-monitor' ),
			)
		);

		$data = array(
			'title'   => $title[ $restriction_type ],
			'content' => $content,
			'icon'    => 'alert'
		);

		self::display_modal_template( $data );

		die();
	}


	/**
	 * Displays modal template based on the data passed. Should be used by other extensions to preserve the modal style.
	 *
	 * @return void
	 * @since 4.8.11
	 */
	public static function display_modal_template( $data = array() ) {

		if ( empty( $data ) ) {
			return;
		}

		$default_args = array(
			'title'   => '',
			'content' => '',
			'icon'    => '',
		);
		// Ensure all keys are set.
		$data = wp_parse_args( $data, $default_args );

		$scripts = apply_filters( 'dlm_modal_template_scripts', array() );

		// Check Scripts & Dependencies if already enqueued
		$scripts_to_print = array();
		foreach ( $scripts as $script => $script_data ) {

			//also check the main script
			if ( in_array( $script, self::$enqueued_scripts, true ) ) {
				continue;
			}

			// add the script to the enq list.
			$scripts_to_print[] = $script;

			// check if the dependencies are enqueued already.
			foreach ( $script_data['dep'] as $dep => $value ) {

				if ( in_array( $value, self::$enqueued_scripts, true ) ) {
					unset( $scripts[ $script ]['dep'][ $dep ] );
				}
			}

			//enqueue clean script
			wp_enqueue_script(
				$script,
				$script_data['path'],
				$script_data['dep'],
				$script_data['version'],
				true
			);
		}

		$template_handler = new DLM_Template_Handler();
		ob_start();

		$template_handler->get_template_part(
			'no-access-modal',
			'',
			'',
			array(
				'title'   => $data['title'],
				'content' => '<div class="dlm-modal-content">' . $data['content'] . '</div>',
				'icon'    => $data['icon']
			)
		);
		wp_print_scripts( $scripts_to_print );
		$modal_template = ob_get_clean();
		// Content and variables escaped above.
		// $content variable escaped from extensions as it may include inputs or other HTML elements.
		echo $modal_template; //phpcs:ignore
		wp_die();
	}
}
