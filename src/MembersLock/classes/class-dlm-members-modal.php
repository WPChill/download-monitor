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
		wp_add_inline_script( 'dlm-xhr', 'document.addEventListener("dlm-xhr-modal-data", function(event) { if ("undefined" !== typeof event.detail.headers["x-dlm-members-locked"]) { event.detail.data["action"] = "dlm_members_conditions_modal"; event.detail.data["dlm_modal_response"] = "true"; event.detail.data["dlm_members_form_redirect"] = "' . ( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) . '" }});', 'after' );
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
		// Alright, all good. Load the template.
		ob_start();
		// Load template.
		$template_handler->get_template_part(
			'members-form-modal',
			'',
			plugin_dir_path( DLM_Members_Lock::get_plugin_file() ) . 'templates/',
			array(
				'download'    => $download,
				'unlock_text' => $unlock_text,
				'tmpl'        => $template_handler,
				'form'        => $this->login_form( $page_redirect ),
			)
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
	public function login_form( $page_redirect ) {
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
		?>
		<div class="dlm-flex dlm-min-h-full dlm-flex-1 dlm-flex-col dlm-justify-center">
			<div class="sm:dlm-mx-auto sm:dlm-w-full sm:dlm-max-w-md">				
				<h2 class="dlm-mt-6 dlm-text-center dlm-text-xl dlm-font-bold dlm-leading-9 dlm-tracking-tight dlm-text-gray-900">
				<?php esc_html_e( 'Log in to your account to download the file!', 'download-monitor' ); ?>
				</h2>
			</div>

			<div class="dlm-mt-10 sm:dlm-mx-auto sm:dlm-w-full sm:dlm-max-w-[480px]">
				<div class="dlm-bg-white dlm-px-6 dlm-py-12 dlm-shadow sm:dlm-rounded-lg sm:dlm-px-12">
				<?php
				printf(
					'<form name="%1$s" id="%1$s" action="%2$s" method="post" class="dlm-space-y-6">',
					esc_attr( $args['form_id'] ),
					esc_url( site_url( 'wp-login.php', 'login_post' ) )
				);
				?>
				<div>
					<?php
					printf(
						'<label for="%1$s" class="dlm-block dlm-text-sm dlm-font-medium dlm-leading-6 dlm-text-gray-900">%2$s</label>',
						esc_attr( $args['id_username'] ),
						esc_html( $args['label_username'] )
					);
					?>
					<div class="mt-2">
						<?php
						printf(
							'<input type="text" name="log" id="%1$s" autocomplete="username" class="input dlm-block dlm-w-full dlm-rounded-md dlm-border-0 dlm-py-1.5 dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 placeholder:dlm-text-gray-400 focus:dlm-ring-2 focus:dlm-ring-inset focus:dlm-ring-indigo-600 sm:dlm-text-sm sm:dlm-leading-6" value="%3$s" size="20"%4$s />',
							esc_attr( $args['id_username'] ),
							esc_html( $args['label_username'] ),
							esc_attr( $args['value_username'] ),
							( $args['required_username'] ? ' required="required"' : '' )
						);
						?>
					</div>
					</div>

					<div>
					<?php
					printf(
						'<label for="%1$s" class="dlm-block dlm-text-sm dlm-font-medium dlm-leading-6 dlm-text-gray-900">%2$s</label>',
						esc_attr( $args['id_password'] ),
						esc_html( $args['label_password'] )
					);
					?>
					<div class="dlm-mt-2">
					<?php
					printf(
						'<input type="password" name="pwd" id="%1$s" autocomplete="current-password" spellcheck="false" class="input input dlm-block dlm-w-full dlm-rounded-md dlm-border-0 dlm-py-1.5 dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 placeholder:dlm-text-gray-400 focus:dlm-ring-2 focus:dlm-ring-inset focus:dlm-ring-indigo-600 sm:dlm-text-sm sm:dlm-leading-6" value="" size="20"%3$s />',
						esc_attr( $args['id_password'] ),
						esc_html( $args['label_password'] ),
						( $args['required_password'] ? ' required="required"' : '' )
					);
					?>
					</div>
					</div>
					<div>
					<?php
					printf(
						'<input type="submit" name="wp-submit" id="%1$s" class="button dlm-flex dlm-w-full dlm-justify-center dlm-rounded-md dlm-bg-indigo-600 dlm-px-3 dlm-py-1.5 dlm-text-sm dlm-font-semibold dlm-leading-6 dlm-text-white dlm-shadow-sm hover:dlm-bg-indigo-500 focus-visible:dlm-outline focus-visible:dlm-outline-2 focus-visible:dlm-outline-offset-2 focus-visible:dlm-outline-indigo-600" value="%2$s" />
						<input type="hidden" name="redirect_to" value="%3$s" />',
						esc_attr( $args['id_submit'] ),
						esc_attr( $args['label_log_in'] ),
						esc_url( $args['redirect'] )
					);
					?>
					</div>
					<?php echo wp_kses_post( $login_form_bottom ); ?>
				</form>
				</div>
			</div>
		</div>
		<?php
	}
}
