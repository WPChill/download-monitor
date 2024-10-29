<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Modal class.
 *
 * Class that handles the No Access Modal and what it implies.
 *
 * @since 4.9.0
 */
class DLM_Modal {

	/**
	 * Holds the class object.
	 *
	 * @since 4.9.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Holds the modal defaults.
	 * Used to ensure that all required keys are set.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	public static $modal_defaults = array(
		'title'    => '', // The title of the modal.
		'content'  => '', // The content of the modal.
		'icon'     => '', // The icon of the modal.
		'tailwind' => false, // Defaults to false, but can be set to true by extensions that permit the use of tailwind.
	);

	/**
	 * __construct function.
	 *
	 * @since 4.9.0
	 */
	private function __construct() {
		// The AJAX request for the No Access Modal.
		add_action( 'wp_ajax_nopriv_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Modal object.
	 * @since 4.9.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Modal ) ) {
			self::$instance = new DLM_Modal();
		}

		return self::$instance;
	}

	/**
	 * Display the XHR no access modal
	 *
	 * @return void
	 * @since 4.9.0 - moved from AjaxHandle.php
	 */
	public function xhr_no_access_modal() {
		// Check nonce.
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );
		// Get settings.
		$settings = download_monitor()->service( 'settings' );
		// Check if the download_id and version_id are set.
		if ( ! isset( $_POST['download_id'] ) || ! isset( $_POST['version_id'] ) ) {
			if ( '1' === $settings->get_option( 'xsendfile_enabled' ) ) {
				wp_send_json_error( 'Missing download_id or version_id. X-Sendfile is enabled, so this is a problem.' );
			}
			wp_send_json_error( 'Missing download_id or version_id' );
		}
		// Action to allow the addition of extra scripts and code related to the shortcode.
		do_action( 'dlm_dlm_no_access_shortcode_scripts' );

		$atts    = array(
			'show_message' => 'true',
		);
		$content = '';
		// Check if the no_access_page is set.
		$no_access_page = $settings->get_option( 'no_access_page' );
		$download       = false;
		if ( ! $no_access_page ) {
			ob_start();

			// template handler.
			$template_handler = new DLM_Template_Handler();
			if ( 'empty-download' === $_POST['download_id'] || ! empty( $_POST['modal_text'] ) ) {
				if ( ! empty( $_POST['modal_text'] ) ) {
					echo wp_kses_post( sanitize_text_field( wp_unslash( $_POST['modal_text'] ) ) );
				} else {
					echo '<p>' . esc_html__( 'You do not have permission to download this file.', 'download-monitor' ) . '</p>';
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
							'no_access_message' => ( ( $atts['show_message'] ) ? wp_kses_post( get_option( 'dlm_no_access_error', '' ) ) : '' ),
						)
					);
				} catch ( Exception $exception ) {
					wp_send_json_error( 'No download found' );
				}
			}

			$content = ob_get_clean();
		} else {
			$content = '';
			/**
			 * Filter to show extra notice text permissions when the user has no access to the download
			 *
			 * @hook dlm_do_extra_notice_text
			 *
			 * @default false
			 *
			 * @since 5.0.14
			 */
			if ( ! empty( $_POST['modal_text'] ) && apply_filters( 'dlm_do_extra_notice_text', false ) ) {
				$content .= '<p class="dlm-no-access-notice">' . esc_html( $_POST['modal_text'] ) . '</p>';
			}
			$content .= do_shortcode( apply_filters( 'the_content', get_post_field( 'post_content', $no_access_page ) ) );
			if ( '' === trim( $content ) ) {
				if ( ! empty( $_POST['modal_text'] ) ) {
					$content .= sanitize_text_field( wp_unslash( $_POST['modal_text'] ) );
				} else {
					$content .= '<p>' . __( 'You do not have permission to download this file.', 'download-monitor' ) . '</p>';
				}
			}
		}

		$restriction_type = isset( $_POST['restriction'] ) && 'restriction-empty' !== $_POST['restriction'] ? sanitize_text_field( wp_unslash( $_POST['restriction'] ) ) : 'no_access_page';
		/**
		 * Filter the title of the modal.
		 *
		 * @hook dlm_modal_title
		 *
		 */
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

		/**
		 * Filter the data of the modal.
		 *
		 * @hook  dlm_modal_data
		 *
		 * @param array        $data     The data of the modal.
		 *                               $data structure:
		 *                               string title - The title of the modal.
		 *                               string content - The content of the modal.
		 *                               string icon - The icon of the modal. This is used in combination with the 'dlm_modal_icon_' . $icon hook set in the no-access-modal template.
		 *                               bool tailwind - Whether to use tailwind or not.
		 * @param DLM_Download $download The download object. It may be false if the true download_id is not sent.
		 * @param array        $settings The settings of the plugin.
		 *
		 * @since 4.9.0
		 */
		$data = apply_filters(
			'dlm_modal_data',
			array(
				// The title of the modal
				'title'    => $title[ $restriction_type ],
				// The content of the modal
				'content'  => $content,
				// The icon of the modal
				'icon'     => 'alert',
				// set false for tailwind. this will be modified from extensions that permit the use of tailwind.
				'tailwind' => false,
			),
			$download,
			$settings
		);
		// Dispaly the modal template.
		self::display_modal_template( $data );

		wp_die();
	}

	/**
	 * Displays modal template based on the data passed. Should be used by other extensions to preserve the modal style.
	 *
	 * @return void
	 * @since 4.9.0
	 */
	public static function display_modal_template( $data = array() ) {
		// Return if we have no data.
		if ( empty( $data ) ) {
			return;
		}

		// Ensure all keys are set.
		$data             = wp_parse_args( $data, self::$modal_defaults );
		$template_handler = new DLM_Template_Handler();
		// Print scripts, dependencies and inline scripts in an object, so we can attach it to the modal.
		ob_start();
		// print_emoji_styles is deprecated and triggers a PHP warning
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		wp_print_styles();
		wp_print_scripts();
		// re-add print_emoji_styles
		add_action( 'wp_print_styles', 'print_emoji_styles' );
		// Get the scripts and styles needed so we can attach them to the modal content.
		$scripts = ob_get_clean();
		// Start the modal template.
		ob_start();
		$template_handler->get_template_part(
			DLM_Constants::DLM_MODAL_TEMPLATE,
			'',
			'',
			array(
				'title'   => $data['title'],
				'content' => '<div class="dlm-modal-content' . ( ! $data['tailwind'] ? ' dlm-no-tailwind' : '' ) . '">' . $scripts . $data['content'] . '</div>',
				'icon'    => $data['icon'],
			)
		);
		// Get the modal template markup.
		$modal_template = ob_get_clean();
		// Content and variables escaped above.
		// $content variable escaped from extensions as it may include inputs or other HTML elements.
		echo apply_filters( 'dlm_modal_content_output', $modal_template ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
}
