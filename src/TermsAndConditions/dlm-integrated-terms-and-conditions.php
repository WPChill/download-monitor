<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DLM_Integrated_Terms_And_Conditions
 *
 * Class to handle the Terms and Conditions integration with Download Monitor.
 * Former Download Monitor Terms & Conditions has been integrated into the core plugin.
 *
 * @since 5.0.0
 */
class DLM_Integrated_Terms_And_Conditions {

	public function __construct() {
		// Download Access Manager.
		$access_manager = new DLM_TC_Access_Manager();
		$access_manager->setup();

		// Download Log Manager.
		$log_manager = new DLM_TC_Log_Manager();
		$log_manager->setup();

		// add shortcode.
		$shortcodes = new DLM_TC_Shortcodes();
		$shortcodes->register();

		// Page Addon compatibility
		$page_addon_compat = new DLM_TC_Page_Addon();
		$page_addon_compat->setup();

		// No Access Modal
		$modal = new DLM_TC_Modal();

		// no access page.
		add_action( 'dlm_no_access_after_message', array( $this, 'add_to_no_access_page' ) );
		// add shortcode scripts to the no access page.
		add_action( 'dlm_no_access_after_message', array( $this, 'add_scripts_to_no_access_page' ) );
		// Admin only classes.
		if ( is_admin() ) {
			// Download Option.
			$download_option = new DLM_TC_Download_Option();
			$download_option->setup();

			// settings.
			$options = new DLM_TC_Options();
			$options->setup();
		}
	}

	/**
	 * Get the plugin file
	 *
	 * @static
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Add tweet button to no access page
	 *
	 * @param  DLM_Download  $download
	 */
	public function add_to_no_access_page( $download ) {
		if ( DLM_TC_Access_Manager::is_tc_locked( $download->get_id() ) ) {
			wp_enqueue_style( 'dlm-frontend' );

			// template handler
			$template_handler = new DLM_Template_Handler();

			// enqueue shortcode JS
			DLM_TC_Assets::enqueue_shortcode_js();

			// unlock text
			$terms_page_id = get_option( 'dlm_tc_content_page', false );
			$unlock_text   = apply_filters( 'dlm_tc_unlock_text', get_option( 'dlm_tc_text', __( 'I accept the terms & conditions', 'download-monitor' ) ), $download );

			$terms_page = ( $terms_page_id && '0' !== $terms_page_id ) ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" target="_blank">' . wp_kses_post( get_the_title( $terms_page_id ) ) . '</a>' : '';

			$unlock_text = str_replace( '%%terms_conditions%%', $terms_page, $unlock_text );

			// load template
			$template_handler->get_template_part( 'tc-form', '', plugin_dir_path( DLM_Integrated_Terms_And_Conditions::get_plugin_file() ) . 'templates/', array(
				'download'    => $download,
				'unlock_text' => $unlock_text,
				'tmpl'        => $template_handler,
			) );
		}
	}

	/**
	 * Add terms and conditions shortcode scripts to no access modal
	 *
	 * @since 5.0.0
	 */
	public function add_scripts_to_no_access_page() {
		if ( isset( $_REQUEST['action'] ) && 'no_access_dlm_xhr_download' === sanitize_Text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
			echo '<script src="' . esc_url( plugins_url( '/assets/js/dlm-terms-and-conditions' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', DLM_Integrated_Terms_And_Conditions::get_plugin_file() ) ) . '"></script>';
		}
	}
}
