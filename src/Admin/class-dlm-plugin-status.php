<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Plugin_Status class.
 *
 * @since 4.9.6
 */
class DLM_Plugin_Status {

	/**
	 * Holds the class object.
	 *
	 * @since 4.9.6
	 *
	 * @var object
	 */
	public static $instance;

	private function __construct() {
		$this->set_hooks();
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Plugin_Status object.
	 * @since 4.9.6
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Plugin_Status ) ) {
			self::$instance = new DLM_Plugin_Status();
		}

		return self::$instance;
	}

	/**
	 * Set required hooks
	 *
	 * @since 4.9.6
	 */
	private function set_hooks() {
		// Add Templates tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'status_tab' ), 15, 1 );
		// Show the templates tab content.
		add_action( 'dlm_tab_section_content_templates', array( $this, 'templates_content' ) );
		// Add tests to the Site Health Info page.
		add_filter( 'site_status_tests', array( $this, 'add_wp_tests' ), 30, 1 );
		// Add required modules to the Site Health Info page.
		add_filter( 'site_status_test_php_modules', array( $this, 'check_modules' ), 30, 1 );

		add_action( 'network_admin_menu', array( $this, 'network_downloads_settings' ), 30, 1 );
		add_action( 'update_wpmu_options', array( $this, 'save_network_downloads_settings' ) );
		//add_action( 'dlm_after_install_setup', array( $this, 'download_path_backwards_compat' ) );
		add_filter( 'dlm_downloadable_file_version_buttons', array( $this, 'browse_files_button' ) );
		
	}

	/**
	 * Disables the browse files button network wide.
	 *
	 * @param  array  $buttons  Array of buttons.
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public function browse_files_button( $buttons ){
		// Check if it's a multisite installation.
		if( ( defined( 'MULTISITE' ) && MULTISITE )){
			// Getting network-wide DLM settings.
			$settings = get_site_option( 'dlm_network_settings', array() );

			// Check if we should remove file browser button.
			if( isset( $settings['dlm_turn_off_file_browser'] ) && '1' == $settings['dlm_turn_off_file_browser'] ){
				unset( $buttons['browse_for_file'] );
			}
		}
		return $buttons;
	}

	/**
	 * Backwards compatibility for multisite environments using dlm_downloads_path setting.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public function download_path_backwards_compat( $first_install ){

		// Don't do backwards compat for first install
		if( $first_install ){
			return;
		}
		// Get the site's current dlm_downloads_path value.
		$site_option = get_option( 'dlm_downloads_path', false );

		// Only do backwards for multisite that have dlm_downloads_path values.
		if ( is_multisite() && $site_option && '' != $site_option ) {

			// Create the network settings array.
			$settings = array( 'dlm_downloads_path' => array( array( 'id' => 1, 'path_val' => $site_option, 'enabled' => true ) ), 'dlm_crossite_file_browse' => '0', 'dlm_turn_off_file_browser' => '0' );

			// Save the network wide option.
			update_site_option( 'dlm_network_settings', $settings );

			// Delete the site specific option.
			delete_option( 'dlm_downloads_path' );
		}
	}

	/**
	 * Add Status tab in the Download Monitor's settings page.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return array
	 * @since 4.9.6
	 */
	public function status_tab( $settings ) {

		$settings['general']['sections']['templates'] = array(
			'title'  => __( 'Templates', 'download-monitor' ),
			'fields' => array(
				// Add empty title field to show the templates tab, otherwise it won't show because of the
				// "Hide empty sections" setting when having a license.
				array(
					'name'     => '',
					'type'     => 'title',
					'title'    => __( '', 'download-monitor' ),
					'priority' => 10,
				),
			),
		);

		return $settings;
	}

	/**
	 * Add Status tab in the Download Monitor's settings page.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function network_downloads_settings() {
		$settings = new DLM_Settings_Page();
		add_menu_page( 
			esc_html__( 'Downloads','download-monitor' ),
			esc_html__( 'Downloads','download-monitor' ),
			'manage_network',
			'download-monitor-settings',
			array( $this, 'network_downloads_settings_page' ),
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA1IiBoZWlnaHQ9IjEwNSIgdmlld0JveD0iMCAwIDEwNSAxMDUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik01Mi41IDAuMDAwNTk5Njc0QzM4LjU3NTYgMC4wMDA1OTk2NzQgMjUuMjIxOSA1LjUzMjAzIDE1LjM3NzYgMTUuMzc4MUM1LjUzMTQ2IDI1LjIyMjkgMCAzOC41NzY2IDAgNTIuNTAwM0MwIDY2LjQyNCA1LjUzMTQ2IDc5Ljc3ODMgMTUuMzc3NiA4OS42MjI1QzI1LjIyMjUgOTkuNDY4NiAzOC41NzYyIDEwNSA1Mi41IDEwNUM2Ni40MjM4IDEwNSA3OS43NzgxIDk5LjQ2ODYgODkuNjIyNCA4OS42MjI1Qzk5LjQ2ODUgNzkuNzc3NyAxMDUgNjYuNDI0IDEwNSA1Mi41MDAzQzEwNSA0My4yODQ1IDEwMi41NzQgMzQuMjMwOCA5Ny45NjY0IDI2LjI1MDJDOTMuMzU4NyAxOC4yNjk1IDg2LjczMDQgMTEuNjQxNiA3OC43NDk3IDcuMDMzNTRDNzAuNzY5IDIuNDI1ODEgNjEuNzE1MiAwIDUyLjQ5OTQgMEw1Mi41IDAuMDAwNTk5Njc0Wk00MC40Nzc3IDM4LjI3MThMNDcuMjQ5OSA0NS4wOTY5VjI2LjI0OTZINTcuNzUwMVY0NS4wOTY5TDY0LjUyMjMgMzguMzI0Nkw3MS45MjUyIDQ1LjcyNzVMNTIuNSA2NS4xNTI2TDMzLjAyMiA0NS42NzQ3TDQwLjQ3NzcgMzguMjcxOFpNNzguNzQ5MSA3OC43NTExSDI2LjI0ODVWNjguMjUxSDc4Ljc0OTFWNzguNzUxMVoiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo=',
			35
		);
	}


	/**
	 * Save network wide settings.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function save_network_downloads_settings(){

		if( ! isset( $_POST['dlm_update_network_options'] ) ){
			return;
		}

		check_admin_referer( 'siteoptions' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'download-monitor' ), 403 );
		}
		$settings        = get_site_option( 'dlm_network_settings', array() );
		
		$downloads_paths = isset( $settings['dlm_downloads_path'] ) ? $settings['dlm_downloads_path'] : array();
		$downloads_path  = isset( $_POST['dlm_downloads_path'] ) ? sanitize_text_field( $_POST['dlm_downloads_path'] ) : '';

		// Handle Edit/Add
		if( isset( $_POST['path_action'] ) && 'edit' == $_POST['path_action'] ) {
			if( ! isset( $_POST['id'] ) || 0 == $_POST['id'] ){
				$lastkey = array_key_last( $downloads_paths );
				$newval = array( 'id' => absint( $downloads_paths[$lastkey]['id'] ) + 1, 'path_val' => $downloads_path, 'enabled' => isset( $_POST['dlm_downloads_path_enabled'] ) );
				$downloads_paths[] = $newval;
			}
	
			if( isset( $_POST['id'] ) &&  0 != $_POST['id'] ){
				foreach( $downloads_paths as $key => $val ) {
					if( $val['id'] == absint( $_POST['id'] ) ){
						$downloads_paths[$key]['path_val'] = $downloads_path;
						$downloads_paths[$key]['enabled'] = isset( $_POST['dlm_downloads_path_enabled'] );
					}
				}
			}
			$cross_browse  = isset( $settings['dlm_crossite_file_browse'] ) ? absint( $settings['dlm_crossite_file_browse'] ) : 0;
			$browse_button = isset( $settings['dlm_turn_off_file_browser'] ) ? absint( $settings['dlm_turn_off_file_browser'] ) : 0;
		}else{
			$cross_browse  = isset( $_POST['dlm_crossite_file_browse'] ) ? absint( $_POST['dlm_crossite_file_browse'] ) : 0;
			$browse_button = isset( $_POST['dlm_turn_off_file_browser'] ) ? absint( $_POST['dlm_turn_off_file_browser'] ) : 0;
		}

		// Handle Bulk
		if( isset( $_POST['otherdownloadpath'] ) ){
			foreach ( $_POST['otherdownloadpath'] as $id ){
				if( isset( $_POST['action'] ) && 'enable' == $_POST['action'] ) {
					foreach ( $downloads_paths as $key => $path ){
						if( $path['id'] == absint( $id ) ){
							$downloads_paths[$key]['enabled'] = true;
							break;
						}
					}
				}
				if( isset( $_POST['action'] ) && 'disable' == $_POST['action'] ) {
					foreach ( $downloads_paths as $key => $path ){
						if( $path['id'] == absint( $id ) ){
							$downloads_paths[$key]['enabled'] = false;
							break;
						}
					}
				}
				if( isset( $_POST['action'] ) && 'delete' == $_POST['action'] ) {
					foreach ( $downloads_paths as $key => $path ){
						if( $path['id'] == absint( $id ) ){
							unset( $downloads_paths[$key] );
							break;
						}
					}
				}
			}
		}


		$settings = array( 'dlm_downloads_path' => $downloads_paths, 'dlm_crossite_file_browse' => $cross_browse, 'dlm_turn_off_file_browser' => $browse_button );
		$settings = apply_filters( 'dlm_saving_network_settings', $settings );
		
		update_site_option( 'dlm_network_settings', $settings );
		
		wp_redirect( add_query_arg( 'page', 'download-monitor-settings', network_admin_url( 'admin.php' ) ) );
		exit;
	}

	
	/**
	 * Render Download Monitor's network admin settings page.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function network_downloads_settings_page(){

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'download-monitor' ), 403 );
		}
		$network_settings = array();
		if( ! isset( $_GET['action'] ) || 'edit' != $_GET['action'] ) {		
			$network_settings = array(
				'dlm_turn_off_file_browser' => array(
					'name'     => 'dlm_turn_off_file_browser',
					'std'      => '',
					'label'    => __( 'Global disable file browser', 'download-monitor' ),
					'cb_label' => '',
					'desc'     => __( 'Disables the directory file browser.', 'download-monitor' ),
					'type'     => 'checkbox',
				),
				'dlm_crossite_file_browse' => array(
					'name'     => 'dlm_crossite_file_browse',
					'std'      => '',
					'label'    => __( 'Allow cross-site file browse', 'download-monitor' ),
					'cb_label' => '',
					'desc'     => __( 'Allows the cross-site browsing of uploads folder for all sites in this network.', 'download-monitor' ),
					'type'     => 'checkbox',
				),
			);
		}


		$network_settings = apply_filters( 'dlm_network_admin_settings', $network_settings );
		$network_options  = get_site_option( 'dlm_network_settings' );
		
		echo '<div class="wrap dlm-admin-settings">';

		echo '<h2>'. esc_html__( 'Download Monitor network settings', 'download-monitor' ) .'</h2>';
		/**
		 * Hook to add content to the start of page
		 *
		 * @param  array  $settings  The settings array
		 */
		do_action( 'dlm_network_admin_settings_before_wrap');

		echo '<form method="post" action="settings.php" novalidate="novalidate" class="dlm-content-tab-full">';
			wp_nonce_field( 'siteoptions' );
			/**
			 * Hook to add content to the start of the settings form.
			 *
			 * @param  array  $settings  The settings array
			 */
			do_action( 'dlm_network_admin_settings_form_start');
			echo '<input type="hidden" value="1" name="dlm_update_network_options" />';
			echo '<div class="dlm-content-tab">';
				echo '<table class="form-table">';

				foreach ( $network_settings as $option ) {
					$cs = 1;

					if ( ! isset( $option['type'] ) ) {
						$option['type'] = '';
					}

					$value = isset( $network_options[$option['name']] ) ? $network_options[$option['name']] : '';
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

					switch ( $option['type'] ) {
						case 'text':
							$field = new DLM_Admin_Fields_Field_Text( $option['name'], $value, '' );
							break;
						case 'checkbox':
							$field = new DLM_Admin_Fields_Field_Checkbox( $option['name'], $value, $option['cb_label'] );
							break;
						default:
							/**
							 * do_filter: dlm_network_setting_field_$type: (null) $field, (array) $network_options, (array) $network_settings.
							 */
							$field = apply_filters( 'dlm_network_setting_field_' . $option['type'], null, $network_options, $network_settings );
							break;
					}

					// check if factory made a field
					if ( null !== $field ) {
						// render field
						$field->render();

						if ( ! empty( $option['desc'] ) ) {
							echo ' <p class="dlm-description description">' . wp_kses_post( $option['desc'] ) . '</p>';
						}
					}

					echo '</td></tr>';
				}

				echo '</table>';
				?>
				<p class="submit">
					<input
						type="submit"
						class="button-primary"
						value="<?php
						echo esc_html__( 'Save Changes', 'download-monitor' ); ?>"/>
				</p>
				<?php

			/**
			 * Hook to add content to the end of page
			 *
			 * @param  array  $settings  The settings array
			 */
			do_action( 'dlm_network_admin_settings_after_wrap');

			echo '</div>';
			/**
			 * Hook to add content to the end of the settings form.
			 *
			 * @param  array  $settings  The settings array
			 */
			do_action( 'dlm_network_admin_settings_form_end');
		echo '</form>';
	}

	/**
	 * Show the templates tab content.
	 *
	 * @since 4.9.6
	 */
	public function templates_content() {
		echo '<div class="wp-clearfix">';
		$theme_info = $this->get_theme_info();

		if ( empty( $theme_info['overrides'] ) ) {
			echo '<h3>' . esc_html__( 'None of Download Monitor\'s output templates are being overridden by your theme.', 'download-monitor' ) . '</h3>';
			echo '</div>';

			return;
		}

		echo '<h3>' . sprintf( esc_html__( 'There are %s overriden templates!', 'download-monitor' ), count( $theme_info['overrides'] ) ) . '</h3>';
		?>
		<table
			class='dlm-template-override'>
			<thead>
			<tr>
				<td>
					<?php
					esc_html_e( 'Overridden file', 'download-monitor' );
					?>
					<div
						class='wpchill-tooltip'>
						<i>[?]</i>
						<div
							class='wpchill-tooltip-content'><?php
							esc_html_e( 'The template that has been overridden.', 'download-monitor' ); ?></div>
					</div>
				</td>
				<td>
					<?php
					esc_html_e( 'Overridden file version', 'download-monitor' );
					?>
					<div
						class='wpchill-tooltip'>
						<i>[?]</i>
						<div
							class='wpchill-tooltip-content'><?php
							esc_html_e( 'The version of the overridden file.', 'download-monitor' ); ?></div>
					</div>
				</td>
				<td>
					<?php
					esc_html_e( 'Core version', 'download-monitor' );
					?>
					<div
						class='wpchill-tooltip'>
						<i>[?]</i>
						<div
							class='wpchill-tooltip-content'><?php
							esc_html_e( 'The version of the core file.', 'download-monitor' ); ?></div>
					</div>
				</td>
				<td>
					<?php
					esc_html_e( 'Status', 'download-monitor' );
					?>
					<div
						class='wpchill-tooltip'>
						<i>[?]</i>
						<div
							class='wpchill-tooltip-content'><?php
							esc_html_e( 'Action status. If core version is bigger than the overridden file version it is recommended to update the overridden file.', 'download-monitor' ); ?></div>
					</div>
				</td>
				<td>
					<?php
					esc_html_e( 'Edit', 'download-monitor' );
					?>
					<div
						class='wpchill-tooltip'>
						<i>[?]</i>
						<div
							class='wpchill-tooltip-content'><?php
							esc_html_e( 'Edit the file using the theme editor.', 'download-monitor' ); ?></div>
					</div>
				</td>
			</tr>
			</thead>
			<tbody>
			<?php
			// Cycle through the overrides and show them in a table.
			foreach ( $theme_info['overrides'] as $override ) {
				$core_version        = ! empty( $override['core_version'] ) ? $override['core_version'] : '-';
				$theme_version       = ! empty( $override['version'] ) ? $override['version'] : '-';
				$theme_version_class = '';
				$needs_update        = false;
				if ( ! empty( $theme_version ) && version_compare( $theme_version, $core_version, '<' ) ) {
					$theme_version_class = ' class="dlm-template-outdated"';
					$needs_update        = true;
				}
				?>
				<tr>
					<td class="dlm-template-file">
						<?php
						echo '<strong>' . esc_html( $override['file'] ) . '</strong>'; ?>
					</td>
					<td class="dlm-template-version">
						<?php
						echo esc_html( $theme_version ); ?>
					</td>
					<td class="dlm-template-core-version">
						<?php
						echo esc_html( $core_version ); ?>
					</td>
					<td class="dlm-template-update <?php
					echo esc_attr( $theme_version_class ) ?>">
						<?php
						if ( $needs_update ) {
							echo '<span class="dashicons dashicons-warning" style="color:red;" title="needs update"></span>';
						} else {
							echo '<span class="dashicons dashicons-yes" style="color:green;"></span>';
						}
						?>
					</td>
					<td class='dlm-template-core-version'>
						<?php
						$edit_url = http_build_query(
							array(
								'file'  => str_replace( $theme_info['template'] . '/', '', $override['file'] ),
								'theme' => $theme_info['template'],
							)
						);
						echo '<a href="' . esc_url( admin_url( 'theme-editor.php?' ) . $edit_url ) . '" class="button button-secondary" target="_blank">' . esc_html__( 'Edit', 'download-monitor' ) . '</a>';
						?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		echo '</div>';
	}

	/**
	 * Get info on the current active theme, info on parent theme
	 * and a list of template overrides.
	 *
	 * @return array
	 *
	 * @since 4.9.6
	 */
	public function get_theme_info() {
		$theme_info = get_transient( 'dlm_templates_info' );

		//if ( false === $theme_info ) {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->template );
			$parent_theme_info = array(
				'parent_name'           => $parent_theme->name,
				'parent_version'        => $parent_theme->version,
				'parent_version_latest' => self::get_latest_theme_version( $parent_theme ),
				'parent_author_url'     => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array(
				'parent_name'           => '',
				'parent_version'        => '',
				'parent_version_latest' => '',
				'parent_author_url'     => '',
			);
		}

		/**
		 * Scan the theme directory for all DLM templates to see if our theme
		 * overrides any of them.
		 */
		$override_files     = array();
		$outdated_templates = false;
		/**
		 * Filter the list of template files to scan. Defaults to all files in the templates directory.
		 *
		 * @hook  dlm_template_files
		 *
		 * @param  array  $scan_files  Array of template files to scan.
		 *
		 * @since 4.9.6
		 */
		$scan_files = apply_filters( 'dlm_template_files', self::scan_template_files( plugin_dir_path( DLM_PLUGIN_FILE ) . '/templates' ) );

		foreach ( $scan_files as $file ) {
			$located = apply_filters( 'dlm_get_template', $file, $file, array(), 'download-monitor', $this->templates_path() );

			if ( file_exists( $located ) ) {
				$theme_file = $located;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . $this->templates_path() . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $this->templates_path() . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $this->templates_path() . $file ) ) {
				$theme_file = get_template_directory() . '/' . $this->templates_path() . $file;
			} else {
				$theme_file = false;
			}

			if ( ! empty( $theme_file ) ) {
				$core_file     = $file;
				$core_version  = self::get_file_version( plugin_dir_path( DLM_PLUGIN_FILE ) . '/templates/' . $core_file );
				$theme_version = self::get_file_version( $theme_file );
				if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
					if ( ! $outdated_templates ) {
						$outdated_templates = true;
					}
				}
				$override_files[] = array(
					'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
					'version'      => $theme_version,
					'core_version' => $core_version,
				);
			}
		}

		$active_theme_info = array(
			'name'                   => $active_theme->name,
			'version'                => $active_theme->version,
			'template'               => $active_theme->template,
			'version_latest'         => self::get_latest_theme_version( $active_theme ),
			'author_url'             => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme'         => is_child_theme(),
			'has_outdated_templates' => $outdated_templates,
			'overrides'              => $override_files,
		);

		$theme_info = array_merge( $active_theme_info, $parent_theme_info );
		set_transient( 'dlm_templates_info', $theme_info, HOUR_IN_SECONDS );

		//	}

		return $theme_info;
	}

	/**
	 * Retrieve metadata from a file. Based on WP Core's get_file_data function.
	 *
	 * @param  string  $file  Path to the file.
	 *
	 * @return string
	 * @since  4.9.6
	 */
	public static function get_file_version( $file ) {
		// Avoid notices if file does not exist.
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' );       // @codingStandardsIgnoreLine.

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 ); // @codingStandardsIgnoreLine.

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );                   // @codingStandardsIgnoreLine.

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * Scan the template files.
	 *
	 * @param  string  $template_path  Path to the template directory.
	 *
	 * @return array
	 * @since 4.9.6
	 */
	public static function scan_template_files( $template_path ) {
		$files  = @scandir( $template_path ); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..' ), true ) ) {
					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}

		return $result;
	}


	/**
	 * Get latest version of a theme by slug.
	 *
	 * @param  object  $theme  WP_Theme object.
	 *
	 * @return string Version number if found.
	 * @since 4.9.6
	 */
	public static function get_latest_theme_version( $theme ) {
		include_once ABSPATH . 'wp-admin/includes/theme.php';

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $theme->get_stylesheet(),
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		$update_theme_version = 0;

		// Check .org for updates.
		if ( is_object( $api ) && ! is_wp_error( $api ) && isset( $api->version ) ) {
			$update_theme_version = $api->version;
		}

		return $update_theme_version;
	}

	/**
	 * Get the path to the templates directory.
	 *
	 * @return string
	 * @since 4.9.6
	 */
	public function templates_path() {
		return apply_filters( 'dlm_template_path', 'download-monitor/' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Add tests to the Site Health Info page.
	 *
	 * @param  array  $tests  Array of tests.
	 *
	 * @return array
	 * @since 4.9.6
	 */
	public function add_wp_tests( $tests ) {
		$tests['direct']['dlm_required_modules'] = array(
			'label' => __( 'Download Monitor required modules / functions' ),
			'test'  => array( $this, 'dlm_required_modules' ),
		);

		return $tests;
	}

	/**
	 * Check if the download meets the requirements to be downloaded.
	 *
	 * @return array
	 * @since 4.9.6
	 *
	 */
	public function dlm_required_functions() {
		$errors = $this->check_requirements();
		// Default good result.
		$result = array(
			'label'       => __( 'DLM - All required modules/functions are active!', 'download-monitor' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Plugin functionality' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Modules and functions help Download Monitor achieve the functionality you desire, and to do that we require that functions and modules, that Download Monitor depends on, be enabled.' )
			),
			'actions'     => '',
			'test'        => 'dlm_required_functions',
		);
		// Check if there are any errors.
		if ( ! empty( $errors ) ) {
			$result = array(
				'label'       => __( 'DLM - One or more functions are missing!', 'download-monitor' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'Plugin functionality' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Modules and functions help Download Monitor achieve the functionality you desire, and to do that we require that functions and modules, that Download Monitor depends on, be enabled.' )
				),
				'actions'     => sprintf(
					'<p>%s</p>',
					__( 'Ask your hosting service to enable the following required modules/functions!', 'download-monitor' )
				),
				'test'        => 'dlm_required_functions',
			);

			// Show functions errors.
			$result['actions'] .= '<strong>' . __( 'Functions:', 'download-monitor' ) . '</strong><ul>';
			foreach ( $errors as $function ) {
				$result['actions'] .= '<li>' . $function . '</li>';
			}
			$result['actions'] .= '</ul>';
		}

		return $result;
	}

	/**
	 * Check if the download meets the requirements to be downloaded.
	 *
	 *
	 * @return array
	 * @since 4.9.6
	 *
	 */
	private function check_requirements() {
		$errors = array();
		/**
		 * Filter the requirements to be checked. Will be completed with more requirements in the future if needed.
		 *
		 * @hook  dlm_health_check_requirements_functions
		 *
		 * @param  array  $checks  Array of requirements to be checked.
		 *
		 * @since 4.9.6
		 */
		$checks = apply_filters(
			'dlm_health_check_requirements_functions',
			array( 'set_time_limit', 'session_write_close', 'ini_set', 'error_reporting' )
		);
		// Let's do the checks for functions.
		if ( ! empty( $checks ) ) {
			foreach ( $checks as $function ) {
				if ( ! function_exists( $function ) ) {
					$errors[] = $function;
				}
			}
		}

		return $errors;
	}

	/**
	 * Check if all the required modules are active.
	 *
	 * @return array
	 * @since 4.9.6
	 *
	 */
	public function check_modules( $modules ) {
		// For the moment we only return the modules from WordPress. Placed here for future use.
		return $modules;
	}
}
