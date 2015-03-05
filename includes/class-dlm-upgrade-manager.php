<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Upgrade_Manager {

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

		}

	}

	/**
	 * Update the current version code
	 */
	private function update_current_version_code() {
		update_option( DLM_Constants::OPTION_CURRENT_VERSION, DLM_VERSION );
	}

}