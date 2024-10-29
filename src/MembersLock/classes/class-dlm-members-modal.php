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
		// Add AJAX action to login the user.
		add_action( 'wp_ajax_dlm_login_member', array( $this, 'login_user' ) );
		add_action( 'wp_ajax_nopriv_dlm_login_member', array( $this, 'login_user' ) );
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
		if ( $settings->get_option( 'no_access_page' ) && apply_filters( 'dlm_use_default_modal', $settings->get_option( 'use_default_modal' ), 'members-only' ) ) {
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
		// enqueue js.
		wp_enqueue_script(
			'dlm_members_lock',
			plugins_url( '/assets/js/members-lock' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', DLM_Members_Lock::get_plugin_file() ),
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
			( apply_filters( 'dlm_modal_dependency_scripts', false, 'dlm_members_lock', 'dlm-members-lock' ) ? array(
				'jquery',
				'wp-i18n',
			) : array() ),
			DLM_VERSION,
			true
		);
		wp_add_inline_script( 'dlm_members_lock', 'var memberLock = { nonce: "' . wp_create_nonce( 'dlm-ajax-nonce' ) . '", ajaxurl: "' . admin_url( 'admin-ajax.php' ) . '" };', 'before' );
		wp_localize_script(
			'dlm_members_lock',
			'dlmMembersLockLang',
			array(
				'required_user' => __( 'User name is required', 'download-monitor' ),
				'required_pass' => __( 'Password is required', 'download-monitor' ),
			)
		);
		$page_redirect = ! empty( $_POST['dlm_members_form_redirect'] ) ? esc_url( $_POST['dlm_members_form_redirect'] ) : get_home_url();
		// Template handler.
		$template_handler = new DLM_Template_Handler();
		$template_args    = array(
			'download' => $download,
			'tmpl'     => $template_handler,
		);
		$template_args    = array_merge( $template_args, $this->login_form_atts( $page_redirect ) );
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
		$args = wp_parse_args( array(), apply_filters( 'login_form_defaults', $defaults ) );

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

	/**
	 * Login the user.
	 *
	 * @since 5.0.14
	 */
	public function login_user() {
		// Check nonce.
		check_ajax_referer( 'dlm-ajax-nonce', 'security' );
		$user_name             = sanitize_text_field( $_POST['user_name'] );
		$user_pass             = sanitize_text_field( $_POST['user_pass'] );
		$info                  = array();
		$info['user_login']    = $user_name;
		$info['user_password'] = $user_pass;
		$info['remember']      = true;
		$user_sigon            = wp_signon( $info, false );
		$download              = false;
		$download_link         = '';
		// Get the Download
		if ( ! empty( $_POST['download_id'] ) ) {
			// Get the Download
			try {
				$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $_POST['download_id'] ) );
			} catch ( Exception $e ) {
			}
		}
		// Check if user managed to log in
		if ( is_wp_error( $user_sigon ) ) {
			wp_send_json_error( $user_sigon->get_error_message() );
		}
		// Found Download? Set download link
		if ( $download ) {
			$download_link .= '<p>' . esc_html__( 'Authentification successful.', 'download-monitor') . '</p>';
			$download_link .= '<p style="padding-top:15px;">' . esc_html__( 'Download link: ', 'download-monitor' ) . '<strong><a href="' . esc_url( $download->get_the_download_link() ) . '">' . esc_html( $download->get_title() ) . '</a></strong><p>';
		}

		wp_send_json_success( $download_link );
	}
}
