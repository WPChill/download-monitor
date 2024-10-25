<?php
/**
 * Handles the modal functionality for the Members only lock.
 *
 * @package DownloadMonitor
 * @since 5.0.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_Members_Modal
 * used to handle the modal functionality for the members only locker.
 *
 * @since 5.0.13
 */
class DLM_Members_Modal {

	/**
	 * Class constructor.
	 *
	 * @since 5.0.13
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'add_members_modal_script' ) );
		add_action( 'wp_ajax_nopriv_dlm_members_conditions_modal', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_dlm_members_conditions_modal', array( $this, 'xhr_no_access_modal' ), 15 );
	}

	/**
	 * Add required scripts to footer.
	 *
	 * @return void
	 * @since 5.0.13
	 */
	public function add_members_modal_script() {
		// Only add the script if the modal template exists.
		// Failsafe, in case the Modal template is non-existent, for example prior to DLM 4.9.0.
		if ( ! class_exists( 'DLM_Constants' ) || ! defined( 'DLM_Constants::DLM_MODAL_TEMPLATE' ) ) {
			return;
		}
		$settings = download_monitor()->service( 'settings' );
		// Check if the no access page is set. If set, we need to go through the normal process.
		// Else we can just add the inline script to handle the subjective modal.
		if ( $settings->get_option( 'no_access_page' ) && apply_filters( 'dlm_use_default_modal_members-only', $settings->get_option( 'use_default_modal' ) ) ) {
			return;
		}
		$script = 'document.addEventListener("dlm-xhr-modal-data", function(event) {'
			. 'if ("undefined" !== typeof event.detail.headers["x-dlm-members-locked"]) {' // Check if we have the right header.
			. 'event.detail.data["action"] = "dlm_members_conditions_modal";' // Set the action.
			. 'event.detail.data["dlm_modal_response"] = "true";' // Set the response.
			. 'event.detail.data["dlm_members_form_redirect"] = "' // Set the redirect URL.
			. esc_url( ( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ) . '";' // Redirect URL.
		. '}'
		. '});';

		wp_add_inline_script( 'dlm-xhr', $script, 'after' );
	}

	/**
	 * Renders the modal contents.
	 *
	 * @return void
	 * @since 5.0.13
	 */
	public function xhr_no_access_modal() {
		// Check nonce.
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		if ( isset( $_POST['download_id'] ) ) {
			// Scripts and styles already enqueued in the shortcode action.
			$title   = '';
			$content = $this->modal_content( absint( $_POST['download_id'] ) );
			DLM_Modal::display_modal_template(
				array(
					'title'    => $title,
					'content'  => '<div id="dlm_login_form">' . $content . '</div>',
					'tailwind' => true,
				)
			);
		}

		wp_die();
	}

	/**
	 * The modal content for the members only lock
	 *
	 * @param int $download_id The download ID.
	 *
	 * @return false|string
	 * @since 5.0.13
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
		$page_redirect = ! empty( $_POST['dlm_members_form_redirect'] ) ? esc_url( $_POST['dlm_members_form_redirect'] ) : get_home_url();
		// Template handler.
		$template_handler = new DLM_Template_Handler();
		// unlock text.
		$terms_page_id = get_option( 'dlm_tc_content_page', false );
		$unlock_text   = apply_filters( 'dlm_tc_unlock_text', get_option( 'dlm_tc_text', __( 'I accept the terms & conditions', 'download-monitor' ) ), $download );
		$terms_page    = ( $terms_page_id && '0' !== $terms_page_id ) ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" target="_blank">' . wp_kses_post( get_the_title( $terms_page_id ) ) . '</a>' : '';
		$unlock_text   = str_replace( '%%terms_conditions%%', $terms_page, $unlock_text );
		$template_args = array(
			'download'    => $download,
			'unlock_text' => $unlock_text,
			'tmpl'        => $template_handler,
		);
		$template_args = array_merge( $template_args, $this->login_form_atts( $page_redirect ) );
		// Alright, all good. Load the template.
		ob_start();
		// Load template.
		$template_handler->get_template_part(
			'members-form-modal',
			'',
			plugin_dir_path( DLM_Members_Lock::get_plugin_file() ) . 'templates/',
			$template_args
		);

		return ob_get_clean();
	}

	/**
	 * Renders the login form.
	 * This is a copy of the WP wp_login_form function with some modifications.
	 *
	 * @param string $page_redirect The URL to redirect to after login.
	 *
	 * @return void|string
	 * @since 5.0.13
	 */
	public function login_form_atts( $page_redirect ) {
		$defaults = array(
			'echo'              => true,
			// Default 'redirect' value takes the user back to the request URI.
			'redirect'          => $page_redirect,
			'form_id'           => 'loginform',
			'label_username'    => __( 'Username or Email Address' ),
			'label_password'    => __( 'Password' ),
			'label_remember'    => __( 'Remember Me' ),
			'label_log_in'      => __( 'Log In' ),
			'id_username'       => 'user_login',
			'id_password'       => 'user_pass',
			'id_remember'       => 'rememberme',
			'id_submit'         => 'wp-submit',
			'remember'          => true,
			'value_username'    => '',
			// Set 'value_remember' to true to default the "Remember me" checkbox to checked.
			'value_remember'    => false,
			// Set 'required_username' to true to add the required attribute to username field.
			'required_username' => false,
			// Set 'required_password' to true to add the required attribute to password field.
			'required_password' => false,
		);

		/**
		 * Filters the default login form output arguments.
		 *
		 * @since 3.0.0
		 *
		 * @see wp_login_form()
		 *
		 * @param array $defaults An array of default login form arguments.
		 */
		$args = wp_parse_args( $args, apply_filters( 'login_form_defaults', $defaults ) );

		/**
		 * Filters content to display at the top of the login form.
		 *
		 * The filter evaluates just following the opening form tag element.
		 *
		 * @since 3.0.0
		 *
		 * @param string $content Content to display. Default empty.
		 * @param array  $args    Array of login form arguments.
		 */
		$login_form_top = apply_filters( 'login_form_top', '', $args );

		/**
		 * Filters content to display in the middle of the login form.
		 *
		 * The filter evaluates just following the location where the 'login-password'
		 * field is displayed.
		 *
		 * @since 3.0.0
		 *
		 * @param string $content Content to display. Default empty.
		 * @param array  $args    Array of login form arguments.
		 */
		$login_form_middle = apply_filters( 'login_form_middle', '', $args );

		/**
		 * Filters content to display at the bottom of the login form.
		 *
		 * The filter evaluates just preceding the closing form tag element.
		 *
		 * @since 3.0.0
		 *
		 * @param string $content Content to display. Default empty.
		 * @param array  $args    Array of login form arguments.
		 */
		$login_form_bottom = apply_filters( 'login_form_bottom', '', $args );

		return array(
			'args'              => $args,
			'login_form_top'    => $login_form_top,
			'login_form_middle' => $login_form_middle,
			'login_form_bottom' => $login_form_bottom,
		);
	}
}
