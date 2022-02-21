<?php

class DLM_Settings_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item
		add_filter( 'dlm_admin_menu_links', array( $this, 'add_settings_page' ), 30 );

		// catch setting actions
		add_action( 'current_screen', array( $this, 'catch_admin_actions' ) );

		//$this->load_hooks();

		if ( is_admin() ) {
			$this->load_admin_hooks();
		}
	}

	/**
	 * Add settings menu item
	 */
	public function add_settings_page( $links ) {
		// Settings page
		$links[] = array(
				'page_title' => __( 'Settings', 'download-monitor' ),
				'menu_title' => __( 'Settings', 'download-monitor' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'download-monitor-settings',
				'function'   => array( $this, 'settings_page' ),
				'priority'   => 20,
		);

		return $links;
	}

	/**
	 * Catch and trigger admin actions
	 */
	public function catch_admin_actions() {

		if ( isset( $_GET['dlm_action'] ) && isset( $_GET['dlm_nonce'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['dlm_action'] ) );

			// check nonce
			// phpcs:ignore
			if ( ! wp_verify_nonce( $_GET['dlm_nonce'], $action ) ) {
				wp_die( esc_html__( "Download Monitor action nonce failed.", 'download-monitor' ) );
			}

			switch ( $action ) {
				case 'dlm_clear_transients':
					$result = download_monitor()->service( 'transient_manager' )->clear_all_version_transients();
					if ( $result ) {
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), DLM_Admin_Settings::get_url() ) );
						exit;
					}
					break;
			}
		}

		if ( isset( $_GET['dlm_action_done'] ) ) {
			add_action( 'admin_notices', array( $this, 'display_admin_action_message' ) );
		}

		$screen = get_current_screen();

		if( $screen->base ==  'dlm_download_page_download-monitor-settings' ) {
			$ep_value = get_option( 'dlm_download_endpoint' );
			$page_check = get_page_by_path( $ep_value );
			$cpt_check  = post_type_exists( $ep_value );

			if( $page_check || $cpt_check ) {
				add_action( 'admin_notices', array( $this, 'display_admin_invalid_ep' ) );
			}
		}

	}

	/**
	 * Display the admin action success mesage
	 */
	public function display_admin_action_message() {

		if ( ! isset( $_GET['dlm_action_done'] ) ) {
			return;
		}

		?>
		<div class="notice notice-success">
			<?php
			switch ( $_GET['dlm_action_done'] ) {
				case 'dlm_clear_transients':
					echo "<p>" . esc_html__( 'Download Monitor Transients successfully cleared!', 'download-monitor' ) . "</p>";
					break;
			}
			?>
		</div>
		<?php
	}

	public function display_admin_invalid_ep() {
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html__( 'The Download Monitor endpoint is already in use by a page or post. Please change the endpoint to something else.', 'download-monitor' ); ?></p>
		</div>
		<?php
	}



	/**
	 * settings_page function.
	 *
	 * @access public
	 * @return void
	 */
	public function settings_page() {

		// initialize settings
		$admin_settings = new DLM_Admin_Settings();
		$settings       = $admin_settings->get_settings();
		$tab            = $this->get_active_tab();

		// print global notices
		$this->print_global_notices();
		?>
		<div class="wrap dlm-admin-settings">
			<form method="post" action="options.php">

				<?php $this->generate_tabs( $settings ); ?>

				<?php

				if ( ! empty( $_GET['settings-updated'] ) ) {
					$this->need_rewrite_flush = true;
					echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Settings successfully saved', 'download-monitor' ) . '</p></div>';

					$dlm_settings_tab_saved = get_option( 'dlm_settings_tab_saved', 'general' );

					echo '<script type="text/javascript">var dlm_settings_tab_saved = "' . esc_js( $dlm_settings_tab_saved ) . '";</script>';
				}

				// loop fields for this tab
				if ( isset( $settings[ $tab ] ) ) {

						$active_section = $this->get_active_section( $settings[ $tab ]['sections'] );

						if ( count( $settings[ $tab ]['sections'] ) >= 1 ) {

							?>
							<div class="wp-clearfix">
							<ul class="subsubsub dlm-settings-sub-nav">
								<?php foreach ( $settings[ $tab ]['sections'] as $section_key => $section ) : ?>
									<?php echo "<li" . ( ( $active_section == $section_key ) ? " class='active-section'" : "" ) . ">"; ?>
									<a href="<?php echo esc_url( add_query_arg( array(
											'tab'     => $tab,
											'section' => $section_key
									), DLM_Admin_Settings::get_url() ) ); ?>"><?php echo esc_html( $section['title'] ); ?><?php echo isset( $section['badge'] ) ?  '<span class="dlm-upsell-badge">PRO</span>' : ''; ?></a></li>
								<?php endforeach; ?>
							</ul>
								</div><!--.wp-clearfix-->
							<h2><?php echo esc_html( $settings[ $tab ]['sections'][ $active_section ]['title'] ); ?></h2>
							<?php
						}

						//echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';
						do_action( 'dlm_tab_section_content_' . $active_section,  $settings );

						if ( isset( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) && ! empty( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) ) {

							// output correct settings_fields
							// We change the output location so that it won't interfere with our upsells
							$option_name = "dlm_" . $tab . "_" . $active_section;
							settings_fields( $option_name );

							echo '<table class="form-table">';

							foreach ( $settings[ $tab ]['sections'][ $active_section ]['fields'] as $option ) {

								$cs = 1;

								echo '<tr valign="top">';
								if ( isset( $option['label'] ) && '' !== $option['label'] ) {
									echo '<th scope="row"><label for="setting-' . esc_attr( $option['name'] ) . '">' . esc_attr( $option['label'] ) . '</a></th>';
								} else {
									$cs ++;
								}


								echo '<td colspan="' . esc_attr( $cs ) . '">';

								if ( ! isset( $option['type'] ) ) {
									$option['type'] = '';
								}

								// make new field object
								$field = DLM_Admin_Fields_Field_Factory::make( $option );

								// check if factory made a field
								if ( null !== $field ) {
									// render field
									$field->render();

									if ( isset( $option['desc'] ) && '' !== $option['desc'] ) {
										echo ' <p class="dlm-description description">' . wp_kses_post( $option['desc'] ) . '</p>';
									}
								}

								echo '</td></tr>';

							}

							echo '</table>';
						}


					echo '<div class="wpchill-upsells-wrapper">';

					do_action( 'dlm_tab_content_' . $tab, $settings );

					echo '</div>';
				}

				?>
				<div class="wp-clearfix"></div>
				<?php
				if ( isset( $settings[ $tab ] ) &&  ( isset( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) && ! empty( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) ) ) {

					?>
					<p class="submit">
						<input type="submit" class="button-primary"
							   value="<?php echo esc_html__( 'Save Changes', 'download-monitor' ); ?>"/>
					</p>
				<?php } ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Print global notices
	 */
	private
	function print_global_notices() {

		// check for nginx
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) &&
			stristr( sanitize_text_field( wp_unslash($_SERVER['SERVER_SOFTWARE']) ), 'nginx' ) !== false &&
			1 != get_option( 'dlm_hide_notice-nginx_rules', 0 ) ) {

			// get upload dir
			$upload_dir = wp_upload_dir();

			// replace document root because nginx uses path from document root
			// phpcs:ignore
			$upload_path = str_replace( sanitize_text_field( wp_unslash($_SERVER['DOCUMENT_ROOT']) ), '', $upload_dir['basedir'] );

			// form nginx rules
			$nginx_rules = "location " . $upload_path . "/dlm_uploads {<br/>deny all;<br/>return 403;<br/>}";
			echo '<div class="error notice is-dismissible dlm-notice" id="nginx_rules" data-nonce="' . esc_attr( wp_create_nonce( 'dlm_hide_notice-nginx_rules' ) ) . '">';
			echo '<p>' . esc_html__( "Because your server is running on nginx, our .htaccess file can't protect your downloads.", 'download-monitor' );
			echo '<br/>' . sprintf( esc_html__( "Please add the following rules to your nginx config to disable direct file access: %s", 'download-monitor' ), '<br/><br/><code class="dlm-code-nginx-rules">' . wp_kses_post( $nginx_rules ) . '</code>' ) . '</p>';
			echo '</div>';
		}

	}

	/**
	 * Load our admin hooks
	 */
	public function load_admin_hooks() {

		add_action( 'in_admin_header', array( $this, 'dlm_page_header' ) );

		add_filter( 'dlm_page_header', array( $this, 'page_header_locations' ) );
	}

	/**
	 * Display the Download Monitor Admin Page Header
	 *
	 * @param bool $extra_class
	 */
	public static function dlm_page_header($extra_class = '') {

		// Only display the header on pages that belong to dlm
		if ( ! apply_filters( 'dlm_page_header', false ) ) {
			return;
		}
		?>
		<div class="dlm-page-header <?php echo ( $extra_class ) ? esc_attr( $extra_class ) : ''; ?>">
			<div class="dlm-header-logo">

				<img src="<?php echo esc_url( DLM_URL . 'assets/images/logo.png' ); ?>" class="dlm-logo" />
			</div>
			<div class="dlm-header-links">
				<a href="https://www.download-monitor.com/kb/" target="_blank" rel="noreferrer nofollow" id="get-help"
				   class="button button-secondary"><span
							class="dashicons dashicons-external"></span><?php esc_html_e( 'Documentation', 'download-monitor' ); ?>
				</a>
				<a class="button button-secondary"
				   href="https://www.download-monitor.com/contact/" target="_blank" rel="noreferrer nofollow"><span
							class="dashicons dashicons-email-alt"></span><?php echo esc_html__( 'Contact us for support!', 'download-monitor' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Set the dlm header locations
	 *
	 * @param $return
	 *
	 * @return bool|mixed
	 * @since 2.5.3
	 */
	public function page_header_locations( $return ) {

		$current_screen = get_current_screen();

		if ( 'dlm_download' === $current_screen->post_type ) {
			return true;
		}

		return $return;
	}

	/**
	 * @param array $settings
	 */
	private
	function generate_tabs( $settings ) {


		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $settings as $key => $section ) {

				// backwards compatibility for when $section did not have 'title' index yet (it simply had the title set at 0)
				$title = ( isset( $section['title'] ) ? $section['title'] : $section[0] );

				echo '<a href="' . esc_url( add_query_arg( 'tab', $key, DLM_Admin_Settings::get_url() ) ) . '" class="nav-tab' . ( ( $this->get_active_tab() === $key ) ? ' nav-tab-active' : '' ) . '">' . esc_html( $title ) . ( ( isset( $section['badge'] ) && true === $section['badge'] ) ? ' <span class="dlm-upsell-badge">PRO</span>' : '' ) . '</a>';
			}
			?>
		</h2>
		<?php
	}

	/**
	 * Returns first key of array
	 *
	 * @param $a
	 *
	 * @return string
	 */
	private
	function array_first_key(
			$a
	) {
		reset( $a );

		return key( $a );
	}

	/**
	 * Return active tab
	 *
	 * @return string
	 */
	private
	function get_active_tab() {
		return ( ! empty( $_GET['tab'] ) ? sanitize_title( wp_unslash($_GET['tab']) ) : 'general' );
	}

	/**
	 * Return active section
	 *
	 * @param $sections
	 *
	 * @return string
	 */
	private function get_active_section( $sections) {
		return ( ! empty( $_GET['section'] ) ? sanitize_title( wp_unslash($_GET['section']) ) : $this->array_first_key( $sections ) );
	}

}
