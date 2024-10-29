<?php
/**
 * Handles the modal functionality for the Terms & Conditions lock.
 *
 * @package DownloadMonitor
 * @since 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_TC_Modal
 * used to handle the modal functionality for the Email Lock extension.
 *
 * @since 5.0.0
 */
class DLM_TC_Modal {

	/**
	 * Class constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'add_tc_modal_script' ) );
		add_action( 'wp_ajax_nopriv_dlm_terms_conditions_modal', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_dlm_terms_conditions_modal', array( $this, 'xhr_no_access_modal' ), 15 );
	}

	/**
	 * Add required scripts to footer.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function add_tc_modal_script() {
		// Only add the script if the modal template exists.
		// Failsafe, in case the Modal template is non-existent, for example prior to DLM 4.9.0.
		if ( ! class_exists( 'DLM_Constants' ) || ! defined( 'DLM_Constants::DLM_MODAL_TEMPLATE' ) ) {
			return;
		}
		$settings = download_monitor()->service( 'settings' );
		// Check if the no access page is set. If set, we need to go through the normal process.
		// Else we can just add the inline script to handle the subjective modal.
		if ( $settings->get_option( 'no_access_page' ) && apply_filters( 'dlm_use_default_modal', $settings->get_option( 'use_default_modal' ), 'dlm-terms-and-conditions' ) ) {
			return;
		}
		wp_add_inline_script( 'dlm-xhr', 'document.addEventListener("dlm-xhr-modal-data", function(event) { if ("undefined" !== typeof event.detail.headers["x-dlm-tc-required"]) { event.detail.data["action"] = "dlm_terms_conditions_modal"; event.detail.data["dlm_modal_response"] = "true"; }});', 'after' );
	}

	/**
	 * Renders the modal contents.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function xhr_no_access_modal() {
		// Check nonce.
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		if ( isset( $_POST['download_id'] ) ) {
			// Scripts and styles already enqueued in the shortcode action.
			$title   = __( 'You need to accept the terms and conditions before you can download.', 'download-monitor' );
			$content = $this->modal_content( absint( $_POST['download_id'] ) );
			DLM_Modal::display_modal_template(
				array(
					'title'    => $title,
					'content'  => '<div id="dlm_terms_conditions_form">' . $content . '</div>',
					'tailwind' => true,
				)
			);
		}

		wp_die();
	}

	/**
	 * The modal content for the Terms & Conditions extension.
	 *
	 * @param int $download_id The download ID.
	 *
	 * @return false|string
	 * @since 5.0.0
	 */
	public function modal_content( $download_id ) {
		// Check for set template before we parse the args as the template might be from Buttons or other addons,
		// and it will override the default form template.

		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
		} catch ( Exception $exception ) {
			// no download found.
			return '';
		}

		// Template handler.
		$template_handler = new DLM_Template_Handler();

		// enqueue js.
		wp_enqueue_script(
			'dlm_tc_frontend',
			plugins_url( '/assets/js/dlm-terms-and-conditions' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', DLM_Integrated_Terms_And_Conditions::get_plugin_file() ),
			/**
			 * Check if we need to load the dependency scripts for our scripts. Loading multiple times same script, jquery in our case,
			 * can cause unwanted behavior. This should be used in every add-on that has a dependency script when using the
			 * No Access Modal.
			 *
			 * @hook: dlm_modal_dependency_scripts
			 *
			 * @param bool   $load_scripts Default value should be false and only true if the dependencies are not already loaded.
			 * @param string $handle       The handle of the script.
			 * @param string $addon_slug   The slug of the add-on. Should be specific for each add-on.
			 */
			( apply_filters( 'dlm_modal_dependency_scripts', false, 'dlm_tc_frontend', 'dlm-terms-and-contitions' ) ? array(
				'jquery',
				'dlm_progress_bar'
			) : array() ),
			DLM_VERSION,
			true
		);

		// unlock text.
		$terms_page_id = get_option( 'dlm_tc_content_page', false );
		$unlock_text   = apply_filters( 'dlm_tc_unlock_text', get_option( 'dlm_tc_text', __( 'I accept the terms & conditions', 'download-monitor' ) ), $download );
		$terms_page    = ( $terms_page_id && '0' !== $terms_page_id ) ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" target="_blank">' . wp_kses_post( get_the_title( $terms_page_id ) ) . '</a>' : '';
		$unlock_text   = str_replace( '%%terms_conditions%%', $terms_page, $unlock_text );
		// Alright, all good. Load the template.
		ob_start();

		// Load template.
		$template_handler->get_template_part( 'tc-form-modal', '', plugin_dir_path( DLM_Integrated_Terms_And_Conditions::get_plugin_file() ) . 'templates/', array(
			'download'    => $download,
			'unlock_text' => $unlock_text,
			'tmpl'        => $template_handler
		) );

		return ob_get_clean();
	}
}
