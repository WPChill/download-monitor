<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

//@todo razvan: Think this class can be removed IF we keep logs by default and there is no option

/**
 * DLM_Logging class.
 */
class DLM_Logging {

	/**
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	// @todo razvan: See what is discussed and keep or remove based on discussion.
	public function is_logging_enabled() {
		return ( 1 == get_option( 'dlm_enable_logging', 0 ) );
	}


	// @todo: This can be deleted if we no longer save IP's
	/**
	 * Get the type of IP logging that is configured in settings
	 *
	 * @return string
	 */
	// @todo razvan: This should be removed as ip storing won't be included in our vision.
	public function get_ip_logging_type() {
		$type = get_option( 'dlm_logging_ip_type', 'full' );
		if ( empty( $type ) ) {
			$type = 'full';
		}

		return $type;
	}

	// @todo razvan: This should be removed as ip storing won't be included in our vision.
	public function is_ua_logging_enabled() {
		return (1==get_option('dlm_logging_ua', 1));
	}

	/**
	 * Check if 'dlm_count_unique_ips' is enabled
	 *
	 * @return bool
	 */
	// @todo razvan: This should be removed as ip storing won't be included in our vision.
	public function is_count_unique_ips_only() {
		return ( '1' == get_option( 'dlm_count_unique_ips', 0 ) );
	}

	/**
	 * Check if visitor has downloaded version
	 *
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	// @todo razvan: This should be removed as ip storing won't be included in our vision.
	public function has_ip_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE `version_id` = %d AND `user_ip` = %s", $version->get_id(), DLM_Utils::get_visitor_ip() ) ) ) > 0 );
	}

}

