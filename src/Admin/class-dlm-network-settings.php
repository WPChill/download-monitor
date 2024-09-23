<?php

/**
 * Class that handles the network wide settings for Download Monitor.
 */
class DLM_Network_Settings {

	/**
	 * The DLM_Downloads_Path_Table
	 *
	 * @var object $table Holds the table object.
	 */
	public $table;

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Network_Settings object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Network_Settings ) ) {
			self::$instance = new DLM_Network_Settings();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		add_action( 'network_admin_menu', array( $this, 'network_downloads_settings' ), 30, 1 );
		add_action( 'update_wpmu_options', array( $this, 'save_network_downloads_settings' ) );
		add_filter( 'dlm_downloadable_file_version_buttons', array( $this, 'browse_files_button' ) );
	}

	/**
	 * Add Status tab in the Download Monitor's settings page.
	 *
	 * @since 5.0.0
	 */
	public function network_downloads_settings() {
		add_menu_page(
			esc_html__( 'Downloads', 'download-monitor' ),
			esc_html__( 'Downloads', 'download-monitor' ),
			'manage_network',
			'download-monitor-settings',
			array( $this, 'network_downloads_settings_page' ),
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA1IiBoZWlnaHQ9IjEwNSIgdmlld0JveD0iMCAwIDEwNSAxMDUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik01Mi41IDAuMDAwNTk5Njc0QzM4LjU3NTYgMC4wMDA1OTk2NzQgMjUuMjIxOSA1LjUzMjAzIDE1LjM3NzYgMTUuMzc4MUM1LjUzMTQ2IDI1LjIyMjkgMCAzOC41NzY2IDAgNTIuNTAwM0MwIDY2LjQyNCA1LjUzMTQ2IDc5Ljc3ODMgMTUuMzc3NiA4OS42MjI1QzI1LjIyMjUgOTkuNDY4NiAzOC41NzYyIDEwNSA1Mi41IDEwNUM2Ni40MjM4IDEwNSA3OS43NzgxIDk5LjQ2ODYgODkuNjIyNCA4OS42MjI1Qzk5LjQ2ODUgNzkuNzc3NyAxMDUgNjYuNDI0IDEwNSA1Mi41MDAzQzEwNSA0My4yODQ1IDEwMi41NzQgMzQuMjMwOCA5Ny45NjY0IDI2LjI1MDJDOTMuMzU4NyAxOC4yNjk1IDg2LjczMDQgMTEuNjQxNiA3OC43NDk3IDcuMDMzNTRDNzAuNzY5IDIuNDI1ODEgNjEuNzE1MiAwIDUyLjQ5OTQgMEw1Mi41IDAuMDAwNTk5Njc0Wk00MC40Nzc3IDM4LjI3MThMNDcuMjQ5OSA0NS4wOTY5VjI2LjI0OTZINTcuNzUwMVY0NS4wOTY5TDY0LjUyMjMgMzguMzI0Nkw3MS45MjUyIDQ1LjcyNzVMNTIuNSA2NS4xNTI2TDMzLjAyMiA0NS42NzQ3TDQwLjQ3NzcgMzguMjcxOFpNNzguNzQ5MSA3OC43NTExSDI2LjI0ODVWNjguMjUxSDc4Ljc0OTFWNzguNzUxMVoiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo=',
			35
		);

		remove_menu_page( 'download-monitor-paths' );
	}


	/**
	 * Save network wide settings.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function save_network_downloads_settings() {
		if ( ! isset( $_POST['dlm_update_network_options'] ) ) {
			return;
		}

		check_admin_referer( 'siteoptions' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'download-monitor' ), 403 );
		}
		$browse_button = isset( $_POST['dlm_turn_off_file_browser'] ) ? absint( $_POST['dlm_turn_off_file_browser'] ) : 0;
		$settings      = apply_filters( 'dlm_saving_network_settings', array( 'dlm_turn_off_file_browser' => $browse_button ) );

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
	public function network_downloads_settings_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'download-monitor' ), 403 );
		}
		$network_settings = array();
		if ( ! isset( $_GET['action'] ) || 'edit' != $_GET['action'] ) {
			$network_settings = array(
				'dlm_turn_off_file_browser' => array(
					'name'     => 'dlm_turn_off_file_browser',
					'std'      => '',
					'label'    => __( 'Global disable file browser', 'download-monitor' ),
					'cb_label' => '',
					'desc'     => __( 'Disables the directory file browser.', 'download-monitor' ),
					'type'     => 'checkbox',
				),
			);
		}

		$network_settings = apply_filters( 'dlm_network_admin_settings', $network_settings );
		$network_options  = get_site_option( 'dlm_network_settings' );

		echo '<div class="wrap dlm-admin-settings">';

		echo '<h2>' . esc_html__( 'Download Monitor network settings', 'download-monitor' ) . '</h2>';
		/**
		 * Hook to add content to the start of page
		 *
		 * @param  array  $settings  The settings array
		 */
		do_action( 'dlm_network_admin_settings_before_wrap' );

		echo '<form method="post" action="settings.php" novalidate="novalidate" class="dlm-content-tab-full">';
		wp_nonce_field( 'siteoptions' );
		/**
		 * Hook to add content to the start of the settings form.
		 *
		 * @param  array  $settings  The settings array
		 */
		do_action( 'dlm_network_admin_settings_form_start' );
		echo '<input type="hidden" value="1" name="dlm_update_network_options" />';
		echo '<div class="dlm-content-tab">';
		echo '<table class="form-table">';

		foreach ( $network_settings as $option ) {
			$cs = 1;

			if ( ! isset( $option['type'] ) ) {
				$option['type'] = '';
			}

			$value    = isset( $network_options[ $option['name'] ] ) ? $network_options[ $option['name'] ] : '';
			$tr_class = 'dlm_settings dlm_' . $option['type'] . '_setting';
			echo '<tr valign="top" data-setting="' . ( isset( $option['name'] ) ? esc_attr( $option['name'] ) : '' ) . '" class="' . esc_attr( $tr_class ) . '">';
			if ( isset( $option['label'] ) && '' !== $option['label'] ) {
				echo '<th scope="row"><label for="setting-' . esc_attr( $option['name'] ) . '">' . esc_attr( $option['label'] ) . '</a></th>';
			} else {
				++$cs;
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
					 *
					 * @since 5.0.0
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
				value="
				<?php
				echo esc_html__( 'Save Changes', 'download-monitor' );
				?>
				"/>
		</p>
		<?php

		/**
		 * Hook to add content to the end of page
		 *
		 * @param  array  $settings  The settings array
		 */
		do_action( 'dlm_network_admin_settings_after_wrap' );

		echo '</div>';
		/**
		 * Hook to add content to the end of the settings form.
		 *
		 * @param  array  $settings  The settings array
		 */
		do_action( 'dlm_network_admin_settings_form_end' );
		echo '</form>';
	}

	/**
	 * Disables the browse files button network wide.
	 *
	 * @param  array $buttons  Array of buttons.
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public function browse_files_button( $buttons ) {
		// Getting network-wide DLM settings.
		$settings = get_site_option( 'dlm_network_settings' );

		// Check if we should remove file browser button.
		if ( isset( $settings['dlm_turn_off_file_browser'] ) && '1' == $settings['dlm_turn_off_file_browser'] ) {
			unset( $buttons['browse_for_file'] );
		}

		return $buttons;
	}
}
