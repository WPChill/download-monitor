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

	private function __construct() {
		$this->set_hooks();
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
	 * Set required hooks for the Download Monitor settings.
	 *
	 * @since 5.0.0
	 */
	private function set_hooks() {
		// Add Templates tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'status_tab' ), 15, 1 );
		// Show the approved downloads path tab content.
		add_action( 'dlm_tab_section_content_download_path', array( $this, 'paths_content' ) );
		// Show the approved downloads path tab content for multisite.
		add_action( 'dlm_network_admin_settings_form_start', array( $this, 'paths_content' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_filter( 'pre_update_option', array( $this, 'update_action' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'actions_handler' ) );
		add_action( 'admin_init', array( $this, 'bulk_actions_handler' ) );
		// Hide the save button in the Approved Download Paths tab.
		add_filter( 'dlm_show_save_settings_button', array( $this, 'hide_save_button' ), 15, 3 );
	}

	/**
	 * Register settings for advanced download path.
	 *
	 * @since 5.0.0
	 */
	public function register_setting() {
		$default = array(
			array(
				'id'       => 1,
				'path_val' => trailingslashit( ABSPATH ),
				'enabled'  => true,
			),
			array(
				'id'       => 2,
				'path_val' => trailingslashit( WP_CONTENT_DIR ),
				'enabled'  => true,
			),
		);
		$args    = array(
			'type'    => 'array',
			'default' => $default,
		);

		register_setting( 'dlm_advanced_download_path', 'dlm_downloads_path', $args );
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
			if ( $url_id == $path['id'] ) {
				$existing = $path;
				break;
			}
		}

		$title = $existing
			? __( 'Edit Approved Path', 'download-monitor' )
			: __( 'Add New Approved Path', 'download-monitor' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$submitted    = sanitize_text_field( wp_unslash( $_GET['submitted-url'] ?? '' ) );
		$existing_url = $existing ? $existing['path_val'] : '';
		$enabled      = $existing ? 'enabled' === $existing['enabled'] : true;
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
					<label for='dlm_downloads_path'> <?php
						echo esc_html__( 'Directory URL', 'download-monitor' ); ?> </label>
				</th>
				<td class='forminp'>
					<input name='dlm_downloads_path' id='dlm_downloads_path' type='text' class='input-text regular-input' value='<?php
					echo esc_attr( empty( $submitted ) ? $existing_url : $submitted ); ?>'>
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
		if ( 'dlm_downloads_path' !== $option || ! isset( $_POST['path_action'] ) ) {
			return $value;
		}
		// If there were no previous values, then we are adding a new path.
		if ( empty( $old_value ) ) {
			return array(
				'id'       => 1,
				'path_val' => trailingslashit( $value ),
				'enabled'  => isset( $_POST['dlm_downloads_path_enabled'] ),
			);
		}
		// Add a trailing slash to the path.
		$value    = trailingslashit( $value );
		$add_file = true;
		// Check and see if the path already exists.
		foreach ( $old_value as $save_path ) {
			if ( trailingslashit( $save_path['path_val'] ) === $value ) {
				$add_file = false;
				break;
			}
		}
		// Path already exists, so return the old value.
		if ( ! $add_file ) {
			return $old_value;
		}
		// From this point on, we are adding a new path.
		if ( ! isset( $_POST['id'] ) || 0 == $_POST['id'] ) {
			$lastkey     = array_key_last( $old_value );
			$newval      = array(
				'id'       => absint( $old_value[ $lastkey ]['id'] ) + 1,
				'path_val' => $value,
				'enabled'  => isset( $_POST['dlm_downloads_path_enabled'] ),
			);
			$old_value[] = $newval;

			return $old_value;
		}

		if ( isset( $_POST['id'] ) && 0 != $_POST['id'] ) {
			foreach ( $old_value as $key => $val ) {
				if ( $val['id'] == absint( $_POST['id'] ) ) {
					$old_value[ $key ]['path_val'] = $_POST['dlm_downloads_path'];
					$old_value[ $key ]['enabled']  = isset( $_POST['dlm_downloads_path_enabled'] );
				}

				return $old_value;
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
		if ( isset( $_GET['page'] ) && 'download-monitor-settings' == $_GET['page'] ) {
			$paths = DLM_Downloads_Path_Helper::get_all_paths();
			if ( ! empty( $_GET['action'] ) ) {
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
				wp_safe_redirect( DLM_Downloads_Path_Helper::get_base_url() );
			}
		}
	}

	/**
	 * Handles bulk actions related to download paths.
	 *
	 * @since 5.0.0
	 */
	public function bulk_actions_handler() {
		if ( isset( $_POST['option_page'] ) && 'dlm_advanced_download_path' === $_POST['option_page'] && isset( $_POST['otherdownloadpath'] ) ) {
			$changes = false;
			$paths   = DLM_Downloads_Path_Helper::get_all_paths();
			foreach ( $_POST['otherdownloadpath'] as $id ) {
				if ( ! empty( $_POST['action'] ) ) {
					$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
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
			}

			if ( $changes ) {
				DLM_Downloads_Path_Helper::save_paths( $paths );
			}
			wp_safe_redirect( DLM_Downloads_Path_Helper::get_base_url() );
		}
	}

	/**
	 * Add the templates tab to the settings page.
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
}
