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
	 * Holds the enqueued scripts.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	public static $enqueued_scripts;

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

		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		$settings = download_monitor()->service( 'settings' );

		if ( ! isset( $_POST['download_id'] ) || ! isset( $_POST['version_id'] ) ) {
			if ( '1' === $settings->get_option( 'xsendfile_enabled' ) ) {
				wp_send_json_error( 'Missing download_id or version_id. X-Sendfile is enabled, so this is a problem.' );
			}
			wp_send_json_error( 'Missing download_id or version_id' );
		}

		// Action to allow the addition of extra scripts and code related to the shortcode.
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
	 * @since 4.9.0
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
		$data             = wp_parse_args( $data, $default_args );
		$template_handler = new DLM_Template_Handler();
		// Print scripts, dependencies and inline scripts in an object, so we can attach it to the modal.
		ob_start();
		wp_print_styles();
		wp_print_scripts();
		// Get the scripts and styles needed so we can attach them to the modal content.
		$scripts = ob_get_clean();

		// Non allowed scripts.
		$non_allowed_scripts = apply_filters( 'dlm_modal_non_allowed_scripts', array( 'jquery' ) );

		// Let's search in the string if we have a form/input or not.
		$pattern              = '/<(form|input).*?>/i';
		$form_input_existence = preg_match( $pattern, $data['content'], $matches );

		// Only manipulate the HTML if we need to.
		if ( $form_input_existence || ! empty( $non_allowed_scripts ) ) {
			// Let's manipulate the retrieved content
			$dom_parser = DLM_DOM_Manipulation::get_instance();
			$dom_parser->load_dom( $data['content'] );
			// If there are non-allowed scripts, let's remove them.
			if ( ! empty( $non_allowed_scripts ) ) {
				$dom_parser->remove_scripts( $non_allowed_scripts );
			}
			if ( $form_input_existence ) {
				$dom_parser->set_form_elements_classes();
			}

			$content = $dom_parser->get_html();
		} else {
			$content = $data['content'];
		}

		// Start the modal template.
		ob_start();
		$template_handler->get_template_part(
			DLM_Constants::DLM_MODAL_TEMPLATE,
			'',
			'',
			array(
				'title'   => $data['title'],
				'content' => '<div class="dlm-modal-content">' . $scripts . $content . '</div>',
				'icon'    => $data['icon']
			)
		);

		$modal_template = ob_get_clean();
		// Content and variables escaped above.
		// $content variable escaped from extensions as it may include inputs or other HTML elements.
		echo apply_filters( 'dlm_modal_content_output', $modal_template ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
}
