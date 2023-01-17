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
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=misc' ) ) );
						exit;
					}
					break;
				case 'dlm_regenerate_protection':
					if ( $this->regenerate_protection() ) {
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=misc' ) ) );
						exit;
					}
					break;
				case 'dlm_regenerate_robots':
					if ( $this->regenerate_robots() ) {
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=misc' ) ) );
						exit;
					}
					break;
				case 'dlm_redo_upgrade':
					if ( DLM_Admin_Helper::redo_upgrade() ) {
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=misc' ) ) );
						exit;
					}
					break;
			}
		}

		if ( isset( $_GET['dlm_action_done'] ) ) {
			add_action( 'admin_notices', array( $this, 'display_admin_action_message' ), 8 );
		}

		$screen = get_current_screen();

		if( $screen->base ==  'dlm_download_page_download-monitor-settings' ) {
			$ep_value = get_option( 'dlm_download_endpoint' );
			$page_check = get_page_by_path( $ep_value, 'ARRAY_A', array( 'page', 'post' ) );
			$cpt_check  = post_type_exists( $ep_value );

			if( $page_check || $cpt_check ) {
				add_action( 'admin_notices', array( $this, 'display_admin_invalid_ep' ), 8 );
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
				case 'dlm_regenerate_protection':
					echo "<p>" . esc_html__( '.htaccess file successfully regenerated!', 'download-monitor' ) . "</p>";
					break;
				case 'dlm_regenerate_robots':
					echo "<p>" . esc_html__( 'Robots.txt file successfully regenerated!', 'download-monitor' ) . "</p>";
					break;
				case 'dlm_redo_upgrade':
					echo "<p>" . esc_html__( 'Environment set for Download Monitor database upgrade!', 'download-monitor' ) . "</p>";
					break;
				default:
					echo "<p>" . esc_html__( 'Download Monitor action completed!', 'download-monitor' ) . "</p>";
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
		$active_section = $this->get_active_section( $settings[ $tab ]['sections'] );

		// print global notices
		$this->print_global_notices();
		?>
		<div class="wrap dlm-admin-settings <?php echo esc_attr( $tab ) . ' ' . esc_attr( $active_section ); ?>">
			<hr class="wp-header-end">
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

					if ( count( $settings[ $tab ]['sections'] ) > 1 ) {

						?>
                        <div class="wp-clearfix">
                            <ul class="subsubsub dlm-settings-sub-nav">
								<?php foreach ( $settings[ $tab ]['sections'] as $section_key => $section ) : ?>
									<?php echo "<li" . ( ( $active_section == $section_key ) ? " class='active-section'" : "" ) . ">"; ?>
                                    <a href="<?php echo esc_url( add_query_arg( array(
										'tab'     => $tab,
										'section' => $section_key
									), DLM_Admin_Settings::get_url() ) ); ?>"><?php echo esc_html( $section['title'] ); ?><?php echo isset( $section['badge'] ) ? '<span class="dlm-upsell-badge">PRO</span>' : ''; ?></a></li>
								<?php endforeach; ?>
                            </ul>
                        </div><!--.wp-clearfix-->
                        <h2><?php echo esc_html( $settings[ $tab ]['sections'][ $active_section ]['title'] ); ?></h2>
						<?php
					}

					//echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';
					do_action( 'dlm_tab_section_content_' . $active_section, $settings );

					if ( isset( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) && ! empty( $settings[ $tab ]['sections'][ $active_section ]['fields'] ) ) {

						// output correct settings_fields
						// We change the output location so that it won't interfere with our upsells
						$option_name = "dlm_" . $tab . "_" . $active_section;
						settings_fields( $option_name );

						echo '<table class="form-table">';

						foreach ( $settings[ $tab ]['sections'][ $active_section ]['fields'] as $option ) {

							$cs = 1;

							if ( ! isset( $option['type'] ) ) {
								$option['type'] = '';
							}

							$tr_class = 'dlm_settings dlm_' . $option['type'] . '_setting';
							echo '<tr valign="top" data-setting="' . ( isset( $option['name'] ) ? esc_attr( $option['name'] ) : '' ) . '" class="' . esc_attr( $tr_class ) . '">';
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

		add_filter( 'dlm_settings', array( $this, 'access_files_checker_field' ) );

		add_filter( 'dlm_settings', array( $this, 'robots_files_checker_field' ) );
	}

	/**
	 * Display the Download Monitor Admin Page Header
	 *
	 * @param bool $extra_class
	 */
	public static function dlm_page_header( $extra_class = '' ) {

		// Only display the header on pages that belong to dlm
		if ( ! apply_filters( 'dlm_page_header', false ) ) {
			return;
		}
		?>
        <div class="dlm-page-header <?php echo ( $extra_class ) ? esc_attr( $extra_class ) : ''; ?>">
            <div class="dlm-header-logo">

                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA1IiBoZWlnaHQ9IjEwNSIgdmlld0JveD0iMCAwIDEwNSAxMDUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik01Mi41IDAuMDAwNTk5Njc0QzM4LjU3NTYgMC4wMDA1OTk2NzQgMjUuMjIxOSA1LjUzMjAzIDE1LjM3NzYgMTUuMzc4MUM1LjUzMTQ2IDI1LjIyMjkgMCAzOC41NzY2IDAgNTIuNTAwM0MwIDY2LjQyNCA1LjUzMTQ2IDc5Ljc3ODMgMTUuMzc3NiA4OS42MjI1QzI1LjIyMjUgOTkuNDY4NiAzOC41NzYyIDEwNSA1Mi41IDEwNUM2Ni40MjM4IDEwNSA3OS43NzgxIDk5LjQ2ODYgODkuNjIyNCA4OS42MjI1Qzk5LjQ2ODUgNzkuNzc3NyAxMDUgNjYuNDI0IDEwNSA1Mi41MDAzQzEwNSA0My4yODQ1IDEwMi41NzQgMzQuMjMwOCA5Ny45NjY0IDI2LjI1MDJDOTMuMzU4NyAxOC4yNjk1IDg2LjczMDQgMTEuNjQxNiA3OC43NDk3IDcuMDMzNTRDNzAuNzY5IDIuNDI1ODEgNjEuNzE1MiAwIDUyLjQ5OTQgMEw1Mi41IDAuMDAwNTk5Njc0Wk00MC40Nzc3IDM4LjI3MThMNDcuMjQ5OSA0NS4wOTY5VjI2LjI0OTZINTcuNzUwMVY0NS4wOTY5TDY0LjUyMjMgMzguMzI0Nkw3MS45MjUyIDQ1LjcyNzVMNTIuNSA2NS4xNTI2TDMzLjAyMiA0NS42NzQ3TDQwLjQ3NzcgMzguMjcxOFpNNzguNzQ5MSA3OC43NTExSDI2LjI0ODVWNjguMjUxSDc4Ljc0OTFWNzguNzUxMVoiIGZpbGw9InVybCgjcGFpbnQwX2xpbmVhcl8zN184NSkiLz4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0icGFpbnQwX2xpbmVhcl8zN184NSIgeDE9Ii0zNy41MjkzIiB5MT0iMS4wOTMzNGUtMDYiIHgyPSI5NS45NzY2IiB5Mj0iMTA3Ljg3MSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPgo8c3RvcCBvZmZzZXQ9IjAuMTEwMTEzIiBzdG9wLWNvbG9yPSIjNURERUZCIi8+CjxzdG9wIG9mZnNldD0iMC40NDM1NjgiIHN0b3AtY29sb3I9IiM0MTlCQ0EiLz4KPHN0b3Agb2Zmc2V0PSIwLjYzNjEyMiIgc3RvcC1jb2xvcj0iIzAwOENENSIvPgo8c3RvcCBvZmZzZXQ9IjAuODU1OTk3IiBzdG9wLWNvbG9yPSIjMDI1RUEwIi8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzAyNTM4RCIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo=" class="dlm-logo"/>
            </div>
            <div class="dlm-header-links">
                <?php do_action( 'dlm_page_header_links' ); ?>
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

	/**
	 * Function used to regenerate the .htaccess for the dlm_uploads folder
	 *
	 * @return void
	 * 
	 * @since 4.5.5
	 */
	private function regenerate_protection(){
		$upload_dir = wp_upload_dir();

		$htaccess_path = $upload_dir['basedir'] . '/dlm_uploads/.htaccess';
		$index_path = $upload_dir['basedir'] . '/dlm_uploads/index.html';

		//remove old htaccess and index files 
		if ( file_exists( $htaccess_path ) ) {
			unlink( $htaccess_path );
		}
		if ( file_exists( $index_path ) ) {
			unlink( $index_path );
		}

		//generate new htaccess and index files
		$this->directory_protection();

		//check if the files were created.
		if ( file_exists( $htaccess_path ) && file_exists( $index_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add setting to check if the .htaccess file is there
	 *
	 * @param Array $settings
	 * @return void
	 * 
	 * @since 4.5.5
	 */
	public function access_files_checker_field( $settings ){

		if ( ! self::check_if_dlm_settings() ) {
			return $settings;
		}

		$upload_dir    = wp_upload_dir();
		$htaccess_path = $upload_dir['basedir'] . '/dlm_uploads/.htaccess';
		$icon          = 'dashicons-dismiss';
		$icon_color    = '#f00';
		$icon_text     = __( 'Htaccess is missing.', 'download-monitor' );

		if ( file_exists( $htaccess_path ) ) {
			$icon       = 'dashicons-yes-alt';
			$icon_color = '#00A32A';
			$icon_text  = __( 'You are protected by htaccess.', 'download-monitor' );
		}

		if ( stristr( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'nginx' ) !== false ) {

			$upload_path = str_replace( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ), '', $upload_dir['basedir'] );
			$nginx_rules = "<code class='dlm-code-nginx-rules'>location " . $upload_path . "/dlm_uploads {<br />deny all;<br />return 403;<br />}</code>";

			$nginx_text =  sprintf( __( 'Please add the following rules to your nginx config to disable direct file access: %s', 'download-monitor'), wp_kses_post( $nginx_rules ) );

			$icon       = 'dashicons-dismiss';
			$icon_color = '#f00';
			$icon_text  = sprintf( __( 'Because your server is running on nginx, our .htaccess file can\'t protect your downloads. %s', 'download-monitor' ), $nginx_text );
			$disabled   = true;
		}

		$settings['advanced']['sections']['misc']['fields'][] = array(
			'name'       => 'dlm_regenerate_protection',
			'label'      => __( 'Regenerate protection for uploads folder', 'download-monitor' ),
			'desc'       => __( 'Regenerates the .htaccess file.', 'download-monitor' ),
			'link'       => admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' ) . '&tab=advanced&section=misc',
			'icon'       => $icon,
			'icon-color' => $icon_color,
			'icon-text'  => $icon_text,
			'disabled'   => isset( $disabled ) ? 'true' : 'false',
			'type'       => 'htaccess_status',
			'priority'   => 30
		);

		return $settings;
	}

	/**
	 * Protect the upload dir on activation.
	 *
	 * @access public
	 * @return void
	 * 
	 * @since 4.5.5 // Copied from Installer.php
	 */
	private function directory_protection() {

		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$htaccess_content = "# Apache 2.4 and up
<IfModule mod_authz_core.c>
Require all denied
</IfModule>

# Apache 2.3 and down
<IfModule !mod_authz_core.c>
Order Allow,Deny
Deny from all
</IfModule>";

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => '.htaccess',
				'content' => $htaccess_content
			),
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => 'index.html',
				'content' => ''
			)
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 * Add setting to check if the robots.txt file is there
	 *
	 * @param Array $settings
	 * @return array
	 *
	 * @since 4.5.9
	 */
	public function robots_files_checker_field( $settings ) {

		if ( ! self::check_if_dlm_settings() ) {
			return $settings;
		}

		$transient = get_transient( 'dlm_robots_txt' );

		if ( ! $transient ) {
			$robots_file = "{$_SERVER['DOCUMENT_ROOT']}/robots.txt";
			$response    = wp_remote_get( get_home_url() . '/robots.txt' );

			// default values.
			$transient = array(
				'icon'       => 'dashicons-dismiss',
				'icon_color' => '#f00',
				'text'       => __( 'Robots.txt is missing.', 'download-monitor' ),
			);

			// we don't have an robots.txt.
			if ( is_wp_error( $response ) || '404' === wp_remote_retrieve_response_code( $response ) ) {

				$transient['icon']       = 'dashicons-dismiss';
				$transient['icon_color'] = '#f00';
				$transient['text']       = __( 'Robots.txt is missing.', 'download-monitor' );
				$transient['virtual']    = 'maybe';
				$icon_text               = __( 'Robots.txt file is missing but site may have virtual robots.txt file. If you regenerate this you will loose the restrictions set in the virtual one. Please either update the virtual with the corresponding rules for dlm_uploads or regenerate and update the newly created one with the contents from the virtual file.', 'download-monitor' );
				$transient['text']       = $icon_text;

			} else {
				// we have robots.txt but it's virtual.
				if ( ! file_exists( $robots_file ) ) {
					$transient['icon']       = 'dashicons-dismiss';
					$transient['icon_color'] = '#f00';
					$transient['text']       = __( 'Robots.txt is missing.', 'download-monitor' );
					$transient['virtual']    = 'maybe';
					$icon_text               = __( 'Robots.txt file is missing but site has virtual robots.txt file. If you regenerate this you will loose the restrictions set in the virtual one. Please either update the virtual with the corresponding rules for dlm_uploads or regenerate and update the newly created one with the contents from the virtual file.', 'download-monitor' );
					$transient['text']       = $icon_text;
				} else {

					// we have our rule/ the user is protected.
					if ( stristr( wp_remote_retrieve_body( $response ), 'dlm_uploads' ) ) {
						$transient['protected']  = true;
						$transient['icon']       = 'dashicons-yes-alt';
						$transient['icon_color'] = '#00A32A';
						$transient['text']       = __( 'You are protected by robots.txt.', 'download-monitor' );
					} else {
						// we don't have our rule, the folder is not protected.
						$transient['protected']  = false;
						$transient['icon']       = 'dashicons-dismiss';
						$transient['icon_color'] = '#f00';
						$transient['text']       = __( 'Robots.txt file exists but dlm_uploads folder is not protected.', 'download-monitor' );
					}
				}
			}

			// save our transient.
			set_transient( 'dlm_robots_txt', $transient, DAY_IN_SECONDS );

		}

		// we need to be sure we have icon/icon_color/text.
		$transient = wp_parse_args( $transient, array(
			'icon'       => 'dashicons-dismiss',
			'icon_color' => '#f00',
			'text'       => __( 'Robots.txt is missing.', 'download-monitor' ),
		) );

		$settings['advanced']['sections']['misc']['fields'][] = array(
			'name'       => 'dlm_regenerate_robots',
			'label'      => __( 'Regenerate crawler protection for uploads folder', 'download-monitor' ),
			'desc'       => __( 'Regenerates the robots.txt file.', 'download-monitor' ),
			'link'       => admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' ) . '&tab=advanced&section=misc',
			'icon'       => $transient['icon'],
			'icon-color' => $transient['icon_color'],
			'icon-text'  => $transient['text'],
			'disabled'   => isset( $disabled ) ? 'true' : 'false',
			'type'       => 'htaccess_status',
			'priority'   => 40
		);

		set_transient( 'dlm_robots_txt', $transient, DAY_IN_SECONDS );
		return $settings;
	}

	/**
	 * Function used to regenerate the robots.txt for the dlm_uploads folder
	 *
	 * @return void
	 * 
	 * @since 4.5.9
	 */
	private function regenerate_robots(){

		delete_transient( 'dlm_robots_txt' );

		$robots_file = "{$_SERVER['DOCUMENT_ROOT']}/robots.txt";
		if( ! file_exists( $robots_file ) ) {
			$txt        = 'User-agent: *' . "\n" . 'Disallow: /dlm_uploads/';
			$dlm_robots = fopen( $robots_file, "w" );
			fwrite( $dlm_robots, $txt );

			return true;

		} else {

			$content = file_get_contents( $robots_file );
			if ( ! stristr( $content, 'dlm_uploads' ) ) {

				$dlm_robots = fopen( $robots_file, "w" );
				$txt        = 'User-agent: *' . "\n" . 'Disallow: /dlm_uploads/' . "\n\n" . $content;

				fwrite( $dlm_robots, $txt );
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if this is Download Monitor's settings page
	 *
	 * @return bool
	 */
	public static function check_if_dlm_settings() {

		if ( ! isset( $_GET['post_type'] ) || 'dlm_download' !== $_GET['post_type'] || ! isset( $_GET['page'] ) || 'download-monitor-settings' !== $_GET['page'] ) {
			return false;
		}

		return true;
	}
}



