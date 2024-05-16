<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Downloads_Path class.
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
		add_action( 'dlm_tab_section_content_download_path', array( $this, 'templates_content' ) );
		// Show the approved downloads path tab content for multisite.
		add_action( 'dlm_network_admin_settings_form_start', array( $this, 'templates_content' ) );
		// Add table columns.
		add_filter( 'manage_dlm_download_page_download-monitor-settings_columns', array( $this, 'get_columns' ) );
		// Add table columns for multisite.
		add_filter( 'manage_toplevel_page_download-monitor-settings-network_columns', array( $this, 'get_columns' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_filter( 'pre_update_option', array( $this, 'update_action' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'actions_handler' ) );
		add_action( 'admin_init', array( $this, 'bulk_actions_handler' ) );
	}

	/**
	 * Register settings for advanced download path.
	 *
	 * @since 5.0.0
	 */
	public function register_setting() {
		register_setting( 'dlm_advanced_download_path', 'dlm_downloads_path' );
	}

	/**
	 * Get list columns for the download monitor settings page.
	 *
	 * @return array List columns.
	 * @since 5.0.0
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'path_val' => __( 'URL', 'download-monitor' ),
			'enabled'  => __( 'Enabled', 'download-monitor' ),
		);
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
						'title'    => __( '', 'download-monitor' ),
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
	public function templates_content() {
		$this->table = new DLM_Downloads_Path_Table();
		if ( null === $this->table ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] && isset( $_REQUEST['url'] ) ) {
			$this->edit_screen( (int) $_REQUEST['url'] );

			return;
		}
		// phpcs:enable

		// Show list table.
		$this->table->prepare_items();
		//$this->display_title();
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
			? __( 'Edit Approved Directory', 'download-monitor' )
			: __( 'Add New Approved Directory', 'download-monitor' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$submitted    = sanitize_text_field( wp_unslash( $_GET['submitted-url'] ?? '' ) );
		$existing_url = $existing ? $existing['path_val'] : '';
		$enabled      = $existing ? 'enabled' == $existing['enabled'] : true;
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
		if ( 'dlm_downloads_path' != $option || ! isset( $_POST['path_action'] ) ) {
			return $value;
		}

		if ( ! isset( $_POST['id'] ) || 0 == $_POST['id'] ) {
			$lastkey     = array_key_last( $old_value );
			$newval      = array( 'id' => absint( $old_value[ $lastkey ]['id'] ) + 1, 'path_val' => $value, 'enabled' => isset( $_POST['dlm_downloads_path_enabled'] ) );
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
			if ( isset( $_GET['action'] ) && 'enable' == $_GET['action'] ) {
				foreach ( $paths as $key => $path ) {
					if ( $path['id'] == absint( $_GET['url'] ) ) {
						$paths[ $key ]['enabled'] = true;
						$change                   = true;
						break;
					}
				}
			}
			if ( isset( $_GET['action'] ) && 'disable' == $_GET['action'] ) {
				foreach ( $paths as $key => $path ) {
					if ( $path['id'] == absint( $_GET['url'] ) ) {
						$paths[ $key ]['enabled'] = false;
						$change                   = true;
						break;
					}
				}
			}

			if ( isset( $_GET['action'] ) && 'delete' == $_GET['action'] ) {
				foreach ( $paths as $key => $path ) {
					if ( $path['id'] == absint( $_GET['url'] ) ) {
						unset( $paths[ $key ] );
						$change = true;
						break;
					}
				}
			}

			if ( isset( $_GET['action'] ) && 'enable-all' == $_GET['action'] ) {
				foreach ( $paths as $key => $path ) {
					$paths[ $key ]['enabled'] = true;
					$change                   = true;
				}
			}

			if ( isset( $_GET['action'] ) && 'disable-all' == $_GET['action'] ) {
				foreach ( $paths as $key => $path ) {
					$paths[ $key ]['enabled'] = false;
					$change                   = true;
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
		if ( isset( $_POST['option_page'] ) && 'dlm_advanced_download_path' == $_POST['option_page'] && isset( $_POST['otherdownloadpath'] ) ) {
			$changes = false;
			$paths   = DLM_Downloads_Path_Helper::get_all_paths();
			foreach ( $_POST['otherdownloadpath'] as $id ) {
				if ( isset( $_POST['action'] ) && 'enable' == $_POST['action'] ) {
					foreach ( $paths as $key => $path ) {
						if ( $path['id'] == absint( $id ) ) {
							$paths[ $key ]['enabled'] = true;
							$changes                  = true;
							break;
						}
					}
				}
				if ( isset( $_POST['action'] ) && 'disable' == $_POST['action'] ) {
					foreach ( $paths as $key => $path ) {
						if ( $path['id'] == absint( $id ) ) {
							$paths[ $key ]['enabled'] = false;
							$changes                  = true;
							break;
						}
					}
				}
				if ( isset( $_POST['action'] ) && 'delete' == $_POST['action'] ) {
					foreach ( $paths as $key => $path ) {
						if ( $path['id'] == absint( $id ) ) {
							unset( $paths[ $key ] );
							$changes = true;
							break;
						}
					}
				}
			}

			if ( $changes ) {
				DLM_Downloads_Path_Helper::save_paths( $paths );
			}
			wp_safe_redirect( DLM_Downloads_Path_Helper::get_base_url() );
		}
	}
}
