<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Downloads_Path class.
 *
 * The main class that handles the download paths.
 *
 * @since 5.0.0
 */
class DLM_Downloads_Path {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The WP_List_Table instance used to display approved directories.
	 *
	 * @var DLM_Downloads_Path_Table
	 */
	private $table;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		// Set AJAX hooks.
		add_action( 'wp_ajax_dlm_update_downloads_path', array( $this, 'update_downloads_path' ) );
		add_action( 'wp_ajax_dlm_enable_download_path', array( $this, 'enable_download_path' ) );
		// Set the rest of the hooks.
		$this->set_frontend_hooks();
		$this->set_admin_hooks();
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Downloads_Path object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Downloads_Path ) ) {
			self::$instance = new DLM_Downloads_Path();
		}

		return self::$instance;
	}

	/**
	 * Set required admin hooks for the Download Monitor settings.
	 *
	 * @since 5.0.0
	 */
	private function set_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		// Add Templates tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'status_tab' ), 15, 1 );
		// Show the approved downloads path tab content.
		add_action( 'dlm_tab_section_content_download_path', array( $this, 'paths_content' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_filter( 'pre_update_option', array( $this, 'update_action' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'actions_handler' ) );
		add_action( 'admin_init', array( $this, 'bulk_actions_handler' ) );
		// Hide the save button in the Approved Download Paths tab.
		add_filter( 'dlm_show_save_settings_button', array( $this, 'hide_save_button' ), 15, 3 );
	}

	/**
	 * Set required admin hooks for the Download Monitor settings.
	 *
	 * @since 5.0.0
	 */
	private function set_frontend_hooks() {
		if ( is_admin() ) {
			return;
		}
		// We need to set the setting for the frontend as well, as we need the default return value.
		add_action( 'init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register settings for advanced download path.
	 *
	 * @since 5.0.0
	 */
	public function register_setting() {
		// Register the setting for multisite.
		if ( is_multisite() ) {
			$multi_args = array(
				'type'    => 'array',
				'default' => array(
					'dlm_turn_off_file_browser' => '0',
				),
			);
			register_setting( 'dlm_advanced_download_path', 'dlm_network_settings', $multi_args );

			$site_id = get_current_blog_id();
			// Get the uploads path and URL.
			$uploads_dir = wp_upload_dir();
			// We need the path.
			$uploads_path = $uploads_dir['basedir'];
			if ( ! empty( $_GET['id'] ) && ! empty( $_GET['page'] ) && 'download-monitor-paths' === $_GET['page'] ) {
				$site_id = absint( $_GET['id'] );
			}

			if ( is_main_site( $site_id ) ) {
				$uploads = $uploads_path;
			} else {
				$uploads = $uploads_path . '/sites/' . $site_id;
			}
			// Set the default value.
			$default = array(
				array(
					'id'       => 2,
					'path_val' => trailingslashit( $uploads ),
					'enabled'  => true,
				),
			);
			$args    = array(
				'type'    => 'array',
				'default' => $default,
			);
			register_setting( 'dlm_advanced_download_path', 'dlm_allowed_paths', $args );
		} else {
			// Add the ABSPATH path to the default array.
			$default[] = array(
				'id'       => 1,
				'path_val' => trailingslashit( ABSPATH ),
				'enabled'  => true,
			);
			// Add the WP_CONTENT_DIR path to the default array.
			$default[] = array(
				'id'       => 2,
				'path_val' => trailingslashit( WP_CONTENT_DIR ),
				'enabled'  => true,
			);

			// Backwards compatibility for the uploads path
			$old_user_path = get_option( 'dlm_downloads_path', '' );

			if ( ! empty( $old_user_path ) ) {
				$default[] = array(
					'id'       => 3,
					'path_val' => trailingslashit( $old_user_path ),
					'enabled'  => true,
				);
			}
			// Register the setting for single site.
			$args = array(
				'type'    => 'array',
				'default' => $default,
			);

			register_setting( 'dlm_advanced_download_path', 'dlm_allowed_paths', $args );
		}
	}

	/**
	 * Add Status tab in the Download Monitor's settings page.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return array Updated array of settings.
	 * @since 5.0.0
	 */
	public function status_tab( $settings ) {
		// Only add this option to single-site environments.
		if ( ! defined( 'MULTISITE' ) || ! MULTISITE ) {
			$settings['advanced']['sections']['download_path'] = array(
				'title'         => __( 'Approved Download Paths', 'download-monitor' ),
				'fields'        => array(
					// Add empty title field to show the templates tab, otherwise it won't show because of the
					// "Hide empty sections" setting when having a license.
					array(
						'name'     => '',
						'type'     => 'title',
						'title'    => '',
						'priority' => 10,
					),
				),
				'show_upsells'  => false,
				'contend_class' => 'dlm-content-tab-full',
			);
		}

		return $settings;
	}

	/**
	 * Show the templates tab content.
	 *
	 * @since 5.0.0
	 */
	public function paths_content() {
		// Only show this tab to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '<h3>' . esc_html__( 'You do not have permission to access this page.', 'download-monitor' ) . '</h3>';

			return;
		}

		$this->table = new DLM_Downloads_Path_Table();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] && isset( $_REQUEST['url'] ) ) {
			$this->edit_screen( (int) $_REQUEST['url'] );
			// Echo the referer field so that after submitting the form, the user is redirected to the correct page.
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_url( admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=advanced&section=download_path' ) ) . '">';

			return;
		}
		// phpcs:enable

		// Show list table.
		$this->table->prepare_items();
		$this->table->render_views();
		$this->table->display();
	}

	/**
	 * Renders the editor screen for approved directory URLs.
	 *
	 * @param  int  $url_id  The ID of the rule to be edited (may be zero for new rules).
	 *
	 * @since 5.0.0
	 */
	private function edit_screen( int $url_id ) {
		$paths    = DLM_Downloads_Path_Helper::get_all_paths();
		$existing = false;

		foreach ( $paths as $path ) {
			if ( absint( $url_id ) === absint( $path['id'] ) ) {
				$existing = $path;
				break;
			}
		}

		$title = $existing
			? __( 'Edit Approved Path', 'download-monitor' )
			: __( 'Add New Approved Path', 'download-monitor' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$submitted    = sanitize_text_field( wp_unslash( $_GET['submitted-url'] ?? '' ) );
		$existing_url = $existing ? $existing['path_val'] : ABSPATH;
		$enabled      = $existing && $existing['enabled'];
		// phpcs:enable

		?>
		<h2 class='wc-table-list-header'>
			<?php
			echo esc_html( $title ); ?>
			<?php
			if ( $existing ) : ?>
				<a href="<?php
				echo esc_url( $this->table->get_action_url( 'edit', 0 ) ); ?>" class="page-title-action"><?php
					esc_html_e( 'Add New', 'download-monitor' ); ?></a>
			<?php
			endif; ?>
			<a href="<?php
			echo esc_url( DLM_Downloads_Path_Helper::get_base_url() ); ?> " class="page-title-action"><?php
				esc_html_e( 'Cancel', 'download-monitor' ); ?></a>
		</h2>
		<table class='form-table'>
			<tbody>
			<tr valign='top'>
				<th scope='row' class='titledesc'>
					<label for='dlm_allowed_paths'> <?php
						echo esc_html__( 'Directory URL', 'download-monitor' ); ?> </label>
				</th>
				<td class='forminp'>
					<input name='dlm_allowed_paths' id='dlm_allowed_paths' type='text' class='input-text regular-input large-text' value='<?php
					echo esc_attr( empty( $submitted ) ? $existing_url : $submitted ); ?>' placeholder="<?php
					echo esc_attr( ABSPATH ); ?>">
					<p class='description'><?php
						echo sprintf( __( 'WordPress installation directory is <code>%s</code>', 'download-monitor' ), esc_html( ABSPATH ) ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row' class='titledesc'>
					<label for='dlm_downloads_path_enabled'> <?php
						echo esc_html__( 'Enabled', 'download-monitor' ); ?> </label>
				</th>
				<td class='forminp'>
					<input name='dlm_downloads_path_enabled' id='dlm_downloads_path_enabled' type='checkbox' value='1' <?php
					checked( true, $enabled ); ?>>
				</td>
			</tr>
			</tbody>
		</table>
		<input name='id' type='hidden' value='<?php
		echo esc_attr( $url_id ); ?>'>
		<input name='path_action' type='hidden' value='edit'>
		<?php
	}

	/**
	 * Updates the action for the download path.
	 *
	 * @param  mixed   $value      The new value of the option.
	 * @param  string  $option     The option name.
	 * @param  mixed   $old_value  The old value of the option.
	 *
	 * @return mixed Updated value.
	 * @since 5.0.0
	 */
	public function update_action( $value, $option, $old_value ) {
		if ( 'dlm_allowed_paths' !== $option || ! isset( $_POST['path_action'] ) ) {
			return $value;
		}
		$enable = isset( $_POST['dlm_downloads_path_enabled'] ) && '1' === $_POST['dlm_downloads_path_enabled'] ? true : false;
		// If there were no previous values, then we are adding a new path.
		if ( empty( $old_value ) ) {
			// The allowed paths are in a multidimensional array.
			return array(
				array(
					'id'       => 1,
					'path_val' => trailingslashit( $value ),
					'enabled'  => $enable,
				),
			);
		}

		// Add a trailing slash to the path.
		$value    = trailingslashit( $value );
		$add_file = true;
		// Check and see if the path already exists.
		foreach ( $old_value as $key => $save_path ) {
			if ( trailingslashit( $save_path['path_val'] ) === $value ) {
				if ( $save_path['enabled'] !== $enable ) {
					$old_value[ $key ]['enabled'] = $enable;
				}
				$add_file = false;
				break;
			}
		}

		// Path already exists, so return the old value.
		if ( ! $add_file ) {
			set_transient( 'dlm_allowed_paths_settings', __( 'Path already exists.', 'download-monitor' ), HOUR_IN_SECONDS );

			return $old_value;
		}
		// From this point on, we are adding a new path.
		if ( ! isset( $_POST['id'] ) || 0 == $_POST['id'] ) {
			$lastkey     = array_key_last( $old_value );
			$newval      = array(
				'id'       => absint( $old_value[ $lastkey ]['id'] ) + 1,
				'path_val' => $value,
				'enabled'  => $enable,
			);
			$old_value[] = $newval;

			return $old_value;
		}

		// This is an edit action.
		if ( isset( $_POST['id'] ) && 0 != $_POST['id'] ) {
			foreach ( $old_value as $key => $val ) {
				if ( absint( $val['id'] ) == absint( $_POST['id'] ) ) {
					$old_value[ $key ]['path_val'] = $_POST['dlm_allowed_paths'];
					$old_value[ $key ]['enabled']  = isset( $_POST['dlm_downloads_path_enabled'] );

					return $old_value;
				}
			}
		}

		return $value;
	}

	/**
	 * Handles actions related to download paths.
	 *
	 * @since 5.0.0
	 */
	public function actions_handler() {
		if ( ! isset( $_GET['url'] ) ) {
			return;
		}

		$change = false;
		$check  = false;
		// The check is different for multisite, as the page is different.
		if ( is_multisite() ) {
			$check = isset( $_GET['page'] ) && 'download-monitor-paths' === $_GET['page'];
		} else {
			$check = isset( $_GET['page'] ) && 'download-monitor-settings' === $_GET['page'];
		}
		if ( $check ) {
			$paths = DLM_Downloads_Path_Helper::get_all_paths();
			if ( ! empty( $_GET['action'] ) ) {
				// IF we're on multisite, switch to the desired blog.
				if ( is_multisite() ) {
					$site_id = get_current_blog_id();
					if ( ! empty( $_GET['id'] ) ) {
						$site_id = absint( $_GET['id'] );
						switch_to_blog( $site_id );
					}
				}
				$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
				switch ( $action ) {
					case 'enable':
						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $_GET['url'] ) ) {
								$paths[ $key ]['enabled'] = true;
								$change                   = true;
								break;
							}
						}
						break;
					case 'disable':
						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $_GET['url'] ) ) {
								$paths[ $key ]['enabled'] = false;
								$change                   = true;
								break;
							}
						}
						break;
					case 'delete':
						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $_GET['url'] ) ) {
								unset( $paths[ $key ] );
								$change = true;
								break;
							}
						}
						break;
					case 'enable-all':
						foreach ( $paths as $key => $path ) {
							$paths[ $key ]['enabled'] = true;
							$change                   = true;
						}
						break;
					case 'disable-all':
						foreach ( $paths as $key => $path ) {
							$paths[ $key ]['enabled'] = false;
							$change                   = true;
						}
						break;
					default:
						$paths  = apply_filters( 'dlm_download_paths_action_' . $action, $paths );
						$change = apply_filters( 'dlm_download_paths_change_' . $action, false, $paths );
						break;
				}
			}

			if ( $change ) {
				DLM_Downloads_Path_Helper::save_paths( $paths );
				// If we're on multisite, switch back to the original blog.
				if ( is_multisite() ) {
					restore_current_blog();
					wp_safe_redirect( network_admin_url( 'admin.php?page=download-monitor-paths&id=' . $site_id ) );
				} else {
					wp_safe_redirect( DLM_Downloads_Path_Helper::get_base_url() );
				}
				exit;
			}
		}
	}

	/**
	 * Handles bulk actions related to download paths.
	 *
	 * @since 5.0.0
	 */
	public function bulk_actions_handler() {
		if ( ( ! empty( $_POST['bulk-action'] ) || ! empty( $_POST['bulk-action2'] ) ) && isset( $_POST['otherdownloadpath'] ) ) {
			$changes = false;
			$paths   = DLM_Downloads_Path_Helper::get_all_paths();
			// Get the action. It's one or the other, so we can just check one.
			if ( ! empty( $_POST['bulk-action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['bulk-action'] ) );
			} else {
				$action = sanitize_text_field( wp_unslash( $_POST['bulk-action2'] ) );
			}
			// IF we're on multisite, switch to the desired blog.
			if ( is_multisite() ) {
				$site_id = get_current_blog_id();
				if ( ! empty( $_GET['id'] ) ) {
					$site_id = absint( $_GET['id'] );
					switch_to_blog( $site_id );
				}
			}
			// Cycle through the selected paths.
			foreach ( $_POST['otherdownloadpath'] as $id ) {
				switch ( $action ) {
					case 'enable':

						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $id ) ) {
								$paths[ $key ]['enabled'] = true;
								$changes                  = true;
								break;
							}
						}
						break;
					case 'disable':
						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $id ) ) {
								$paths[ $key ]['enabled'] = false;
								$changes                  = true;
								break;
							}
						}
						break;
					case 'delete':
						foreach ( $paths as $key => $path ) {
							if ( $path['id'] == absint( $id ) ) {
								unset( $paths[ $key ] );
								$changes = true;
								break;
							}
						}
						break;
					default:
						$paths   = apply_filters( 'dlm_download_paths_bulk_actions_' . $action, $paths );
						$changes = apply_filters( 'dlm_download_paths_bulk_change_' . $action, false, $paths );
						break;
				}
			}

			if ( $changes ) {
				DLM_Downloads_Path_Helper::save_paths( $paths );
			}
			// If we're on multisite, switch back to the original blog.
			if ( is_multisite() ) {
				restore_current_blog();
				wp_safe_redirect( network_admin_url( 'admin.php?page=download-monitor-paths&id=' . $site_id ) );
			} else {
				wp_safe_redirect( DLM_Downloads_Path_Helper::get_base_url() );
			}
			exit;
		}
	}

	/**
	 * Hide the save button in the Approved Download Paths tab.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return bool
	 * @since 5.0.0
	 */
	public function hide_save_button( $return, $settings, $active_section ) {
		if ( 'download_path' === $active_section && empty( $_GET['action'] ) ) {
			return false;
		}

		return $return;
	}

	/**
	 * Update downloads path.
	 *
	 * @return void
	 * @since 4.8.0
	 */
	public function update_downloads_path() {
		// Check if the request is valid
		check_ajax_referer( 'dlm-ajax-nonce', 'security' );
		// Check if the path is provided
		if ( ! isset( $_POST['path'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No path provided', 'download-monitor' ) ) );
		}
		// Check if the user has permission to update the path
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to update the path', 'download-monitor' ) ) );
		}
		// Save the new path in the Allowed Paths Table
		DLM_Downloads_Path_Helper::save_unique_path( urldecode( $_POST['path'] ) );
		wp_send_json_success( array( 'message' => __( 'Path updated', 'download-monitor' ) ) );
	}

	/**
	 * Update downloads path.
	 *
	 * @return void
	 * @since 4.8.0
	 */
	public function enable_download_path() {

		// Check if the request is valid
		check_ajax_referer( 'dlm-ajax-nonce', 'security' );
		// Check if the path is provided
		if ( ! isset( $_POST['path'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No path provided', 'download-monitor' ) ) );
		}
		// Check if the user has permission to update the path
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to update the path', 'download-monitor' ) ) );
		}

		$path = urldecode( $_POST['path'] );
		// Save the new path in the Allowed Paths Table
		DLM_Downloads_Path_Helper::enable_download_path( $path );
		wp_send_json_success( array( 'message' => __( 'Path enabled', 'download-monitor' ) ) );
	}
}
