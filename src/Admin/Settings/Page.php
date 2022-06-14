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
					if ( $this->redo_upgrade() ) {
						wp_redirect( add_query_arg( array( 'dlm_action_done' => $action ), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=misc' ) ) );
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
			$page_check = get_page_by_path( $ep_value, 'ARRAY_A', array( 'page', 'post' ) );
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

							$tr_class = ( 'group' === $option['type'] ? 'dlm-groupped-settings' : '' );
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

                <img src="data:image/png;base64,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" class="dlm-logo"/>
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

		if( !$transient ){
			$robots_file = "{$_SERVER['DOCUMENT_ROOT']}/robots.txt";
			$page = wp_remote_get( get_home_url() . '/robots.txt');
			$has_virtual_robots = 'undetermined';

			if ( ! is_wp_error( $page ) && is_array( $page ) ) {
				$has_virtual_robots = false !== strpos( $page['headers']['content-type'], 'text/plain' );
			}
		}

		if ( ( !$transient ) && ! file_exists( $robots_file ) ) {
			$icon       = 'dashicons-dismiss';
			$icon_color = '#f00';
			$icon_text  = __( 'Robots.txt is missing.', 'download-monitor' );
			$transient['icon'] = $icon;
			$transient['icon_color'] = $icon_color;
			$transient['text'] = $icon_text;
			$transient['virtual'] = false;

			if ( $has_virtual_robots && 'undetermined' !== $has_virtual_robots ) {
				$transient['virtual'] = true;
				$icon_text  = __( 'Robots.txt file is missing but site has virtual Robots.txt file. If you regenerate this you will loose the restrictions set in the virtual one. Please either update the virtual with the corresponding rules for dlm_uploads or regenerate and update the newly created one with the contents from the virtual file.', 'download-monitor' );
				$transient['text'] = $icon_text;
			}

			if ( $has_virtual_robots && 'undetermined' === $has_virtual_robots ) {
				$transient['virtual'] = 'maybe';
				$icon_text  = __( 'Robots.txt file is missing but site may have virtual Robots.txt file. If you regenerate this you will loose the restrictions set in the virtual one. Please either update the virtual with the corresponding rules for dlm_uploads or regenerate and update the newly created one with the contents from the virtual file.', 'download-monitor' );
				$transient['text'] = $icon_text;
			}
		} else {
			if( !$transient ){
				$content = file_get_contents( $robots_file );
				if ( stristr( $content, 'dlm_uploads' ) ) {
					$icon       = 'dashicons-yes-alt';
					$icon_color = '#00A32A';
					$icon_text  = __( 'You are protected by robots.txt.', 'download-monitor' );
					$transient['protected'] = true;
					$transient['icon'] = $icon;
					$transient['icon_color'] = $icon_color;
					$transient['text'] = $icon_text;
				} else {
					$icon       = 'dashicons-dismiss';
					$icon_color = '#f00';
					$icon_text  = __( 'Robots.txt file exists but dlm_uploads folder is not protected.', 'download-monitor' );
					$transient['protected'] = false;
					$transient['icon'] = $icon;
					$transient['icon_color'] = $icon_color;
					$transient['text'] = $icon_text;
				}
			}
		}

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
	 * Undocumented function
	 *
	 * @return void
	 */
	public function redo_upgrade() {

		global $wp, $wpdb, $pagenow;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// Drop the dlm_reports_log
		$drop_statement = "DROP TABLE IF EXISTS {$wpdb->prefix}dlm_reports_log";
		$wpdb->query( $drop_statement );

		// Delete upgrade history and set the need DB pgrade
		delete_option( 'dlm_db_upgraded' );
		set_transient( 'dlm_needs_upgrade', '1', 30 * DAY_IN_SECONDS );

		return true;
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



