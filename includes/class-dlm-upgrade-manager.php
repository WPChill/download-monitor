<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Upgrade_Manager {

	/**
	 * Setup to run updater on wp_loaded
	 */
	public function setup() {
		add_action( 'wp_loaded', array( $this, 'check' ) );
	}

	/**
	 * Check if there's a plugin update
	 */
	public function check() {

		// Get current version
		$current_version = get_option( DLM_Constants::OPTION_CURRENT_VERSION, 0 );

		// Check if update is required
		if ( version_compare( DLM_VERSION, $current_version, '>' ) ) {

			// Do update
			$this->do_upgrade( $current_version );

			// Update version code
			$this->update_current_version_code();

		}

	}

	/**
	 * An update is required, do it
	 *
	 * @param $current_version
	 */
	private function do_upgrade( $current_version ) {

		// Upgrade to version 1.7.0
		if ( version_compare( $current_version, '1.7.0', '<' ) ) {

			// Adding new capabilities
			$installer = new DLM_Installer();
			$installer->init_user_roles();

			// Set default 'No access message'
			$dlm_no_access_error = get_option( 'dlm_no_access_error', '' );
			if ( '' === $dlm_no_access_error ) {
				update_option( 'dlm_no_access_error', sprintf( __( 'You do not have permission to access this download. %sGo to homepage%s', 'download-monitor' ), '<a href="' . home_url() . '">', '</a>' ) );
			}

		}

		// Upgrade to version 1.9.0
		if ( version_compare( $current_version, '1.9.0', '<' ) ) {

			// Adding new capabilities
			$installer = new DLM_Installer();
			$installer->create_no_access_page();

			// setup no access page endpoints
			$no_access_page_endpoint = new DLM_Download_No_Access_Page_Endpoint();
			$no_access_page_endpoint->setup();

			// flush rules after page creation
			flush_rewrite_rules();
		}

	}

	/**
	 * Update the current version code
	 */
	private function update_current_version_code() {
		update_option( DLM_Constants::OPTION_CURRENT_VERSION, DLM_VERSION );
	}

}