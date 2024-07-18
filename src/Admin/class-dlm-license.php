<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DLM_License class
 *
 * @description This class is used to manage the license of the plugin.
 *
 * @since       5.0.0
 */
class DLM_License {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Primary class constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		// Add License tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'add_license_tab' ), 90, 1 );
		// Add License tab content
		// Show the templates tab content.
		add_action( 'dlm_tab_section_content_license', array( $this, 'license_page' ) );
		add_filter( 'dlm_show_save_settings_button', array( $this, 'hide_save_button' ), 15, 3 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_License object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_License ) ) {
			self::$instance = new DLM_License();
		}

		return self::$instance;
	}

	/**
	 * Output installed extensions page
	 *
	 * @since 5.0.0
	 */
	public function license_page() {
		/**
		 * Filter to show the license content.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'dlm_lite_license_content', true ) ) {
			return;
		}
		?>
		<div class="wrap dlm_extensions_wrap">
		<?php

		// Installed Extensions
		// WPChill Welcome Class.
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

		if ( ! class_exists( 'WPChill_Welcome' ) ) {
			return;
		}

		echo '<div id="installed-extensions-licenses" class="settings_panel">';
		echo '<div class="dlm_extensions">';
		?>
		<div class="clear">
			<h2><?php
				esc_html_e( 'Please install DLM PRO', 'download-monitor' ); ?></h2>

		</div><!-- .block -->
		<?php
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Add the License tab
	 *
	 * @param  array  $settings  The settings array.
	 *
	 * @since 5.0.0
	 */
	public function add_license_tab( $settings ) {
		$extensions = DLM_Admin_Extensions::get_instance();
		$extensions->load_data();
		$products             = $extensions->get_products();
		if ( ! empty( $products ) ) {
			$settings['license'] = array(
				'title'    => __( 'License', 'download-monitor' ),
				'sections' => array(
					'license' => array(
						'title'  => __( 'License', 'download-monitor' ),
						'fields' => array(
							array(
								'name'     => '',
								'type'     => 'title',
								'title'    => __( '', 'download-monitor' ),
								'priority' => 30,
							),
						),
					),
				),
			);
		}

		return $settings;
	}

	/**
	 * Hide the save button on the license page.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return bool
	 * @since 5.0.0
	 */
	public function hide_save_button( $return, $settings, $active_section ) {
		if ( 'license' === $active_section && empty( $_GET['action'] ) ) {
			return false;
		}

		return $return;
	}
}
