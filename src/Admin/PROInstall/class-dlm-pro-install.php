<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The DLM PRO Install class.
class DLM_PRO_Install {

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
	 * @return object The DLM_PRO_Install object.
	 * @since 5.0.0
	 *
	 */
	public static function get_instance() {
		if ( ! isset( DLM_PRO_Install::$instance ) && ! ( DLM_PRO_Install::$instance instanceof DLM_PRO_Install ) ) {
			DLM_PRO_Install::$instance = new DLM_PRO_Install();
		}

		return DLM_PRO_Install::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 */
	private function __construct() {
		// Add the action to install the plugin.
		add_action( 'admin_init', array( $this, 'install' ), 60 );
	}

	/**
	 * Install the plugin.
	 *
	 * @since 5.0.0
	 *
	 */
	public function install() {
		if ( ! $this->requires_install() ) {
			return;
		}
		$this->do_install();
	}

	/**
	 * Check if the plugin requires installation.
	 *
	 * @return bool True if the plugin requires installation, false otherwise.
	 *
	 * @since 5.0.0
	 */
	public function requires_install() {
		// Check if plugin is already active.
		if ( class_exists( 'DLM_PRO' ) ) {
			return false;
		}
		// Check if plugin is already installed.
		$plugin_path = WP_PLUGIN_DIR . '/dlm-pro/dlm-pro.php';
		if ( file_exists( $plugin_path ) ) {
			return false;
		}

		// Check if we have any license keys.
		$lite_ext   = DLM_Admin_Extensions::get_instance();
		$extensions = $lite_ext->get_licensed_extensions();
		// If not installed, return false.
		if ( empty( $extensions ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Perform the installation of the plugin.
	 *
	 * @since 5.0.0
	 *
	 */
	public function do_install() {
		// Get the license data.
		$lite_ext     = DLM_Admin_Extensions::get_instance();
		$extensions   = $lite_ext->get_licensed_extensions();
		$product      = new DLM_Product( 'dlm-pro' );
		$license      = $product->get_license();
		$download_url = add_query_arg(
			array(
				'download_api_product' => 'dlm-pro',
				'license_key'          => urlencode( $license->get_key() ),
				'activation_email'     => urlencode( $license->get_email() ),
			), DLM_Product::PRODUCT_DOWNLOAD_URL
		);
		// Prepare variables.
		$method = '';
		$url    = add_query_arg(
			array(
				'page'      => 'dlm-extensions',
				'post_type' => 'dlm_download',
			),
			admin_url( 'edit.php' )
		);
		$url    = esc_url( $url );
		if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
			set_transient( 'dlm_pro_install', 'credentials_failed', 60 * 60 );

			return;
		}

		// If we are not authenticated, make it happen now.
		if ( ! WP_Filesystem( $creds ) ) {
			set_transient( 'dlm_pro_install', 'credentials_failed', 60 * 60 );

			return;
		}

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . 'src/Admin/class-dlm-upgrader-skin.php';

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( new DLM_Upgrader_Skin() );
		$installer->install( htmlspecialchars_decode( $download_url ) );
	}
}