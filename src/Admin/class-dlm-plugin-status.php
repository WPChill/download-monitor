<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Plugin_Status class.
 *
 * @since 4.9.5
 */
class DLM_Plugin_Status {

	/**
	 * Holds the class object.
	 *
	 * @since 4.9.5
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
	 * @since 4.9.5
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
	 * @since 4.9.5
	 */
	private function set_hooks() {
		// Add Templates tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'status_tab' ), 15, 1 );
		// Show the templates tab content.
		add_action( 'dlm_tab_section_content_templates', array( $this, 'templates_content' ) );
	}

	/**
	 * Add Status tab in the Download Monitor's settings page.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return array
	 * @since 4.9.5
	 */
	public function status_tab( $settings ) {
		$settings['status'] = array(
			'title'    => __( 'Status', 'download-monitor' ),
			'sections' => array(
				'misc'      => array(
					'title'  => __( 'Miscellaneous', 'download-monitor' ),
					'fields' => array(
						array(
							'name'     => 'dlm_downloads_path',
							'std'      => '',
							'label'    => __( 'Other downloads path', 'download-monitor' ),
							'desc'     => __( '<strong>!!ATTENTION!! ONLY</strong> modify this setting if you know and are certain of what you are doing. This can cause problems on the download/saving Downloads process if not specified correctly. Prior to modifying this it is advised to <strong>BACKUP YOU DATABASE</strong> in case something goes wrong.<br><br> By default, due to some security issues and restrictions, we only allow downloads from root folder and uploads folder, depending on how your WordPress installation in configured. To be able to download files from somewhere else please specify the path or a more higher path.<br><br>A full documentation can be seen <a href="https://www.download-monitor.com/kb/add-path/" target="_blank">here</a>.', 'download-monitor' ),
							'type'     => 'text',
							'priority' => 60,
						),
					),
				),
				'templates' => array(
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
				),
			),
		);

		return $settings;
	}

	/**
	 * Show the templates tab content.
	 *
	 * @since 4.9.5
	 */
	public function templates_content() {
		echo '<div class="wp-clearfix">';
		$theme_info = $this->get_theme_info();

		if ( empty( $theme_info['overrides'] ) ) {
			echo '<h3>' . esc_html__( 'There are no overridden templates', 'download-monitor' ) . '</h3>';

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
	 * @since 4.9.5
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
		 * @since 4.9.5
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
	 * @since  4.9.5
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
	 * @since 4.9.5
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
	 * @since 4.9.5
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
	 * @since 4.9.5
	 */
	public function templates_path() {
		return apply_filters( 'dlm_template_path', 'download-monitor/' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}
}
