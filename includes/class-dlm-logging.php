<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Logging class.
 */
class DLM_Logging {

	/**
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return ( 1 == get_option( 'dlm_enable_logging', 0 ) );
	}

	/**
	 * Check if 'dlm_count_unique_ips' is enabled
	 *
	 * @return bool
	 */
	public function is_count_unique_ips_only() {
		return ( '1' == get_option( 'dlm_count_unique_ips', 0 ) );
	}

	/**
	 * Check if visitor has downloaded version in the past 24 hours
	 *
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	public function has_ip_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE type = 'download' AND `version_id` = %d AND `user_ip` = %s", $version->id, DLM_Utils::get_visitor_ip() ) ) ) > 0 );
	}

	/**
	 * create_log function.
	 *
	 * @access public
	 * @return void
	 */
	public function create_log( $type, $status, $message, $download, $version ) {
		global $wpdb;

		$wpdb->hide_errors();

		$wpdb->insert(
			$wpdb->download_log,
			array(
				'type'                    => $type,
				'user_id'                 => absint( get_current_user_id() ),
				'user_ip'                 => DLM_Utils::get_visitor_ip(),
				'user_agent'              => DLM_Utils::get_visitor_ua(),
				'download_id'             => absint( $download->id ),
				'version_id'              => absint( $version->id ),
				'version'                 => $version->version,
				'download_date'           => current_time( 'mysql' ),
				'download_status'         => $status,
				'download_status_message' => $message
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a log entry
	 *
	 * @param $log_id
	 *
	 * @return bool
	 */
	public function delete_log( $log_id ) {
		global $wpdb;

		return ( false !== $wpdb->delete( $wpdb->download_log, array( 'ID' => $log_id ), array( '%d' ) ) );
	}

}

