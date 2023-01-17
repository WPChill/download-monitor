<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Logging class.
 */
class DLM_Logging {

	/**
	 * Holds the class object.
	 *
	 * @since 4.6.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Class constructor
	 * 
	 * @since 4.6.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_log_dlm_xhr_download', array( $this, 'xhr_log_download' ), 15 );
		add_action( 'wp_ajax_nopriv_log_dlm_xhr_download', array( $this, 'xhr_log_download' ), 15 );
		add_filter( 'dlm_log_entries', array( $this, 'log_entries' ), 15, 2 );
		add_filter( 'dlm_log_values', array( $this, 'log_values' ), 15, 1 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Logging object.
	 * @since 4.6.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Logging ) ) {
			self::$instance = new DLM_Logging();
		}

		return self::$instance;

	}

	/**
	 * Check if logging is enabled.
	 * Modified in version 4.5.0
	 *
	 * @return bool
	 *
	 * @since 4.5.0
	 */
	public static function is_logging_enabled() {

		return apply_filters( 'dlm_enable_reports', true );
	}

	/**
	 * Get the type of IP logging that is configured in settings
	 *
	 * @return string
	 */
	public function get_ip_logging_type() {
		$type = get_option( 'dlm_logging_ip_type', 'full' );
		if ( empty( $type ) ) {
			$type = 'full';
		}

		return $type;
	}

	public function is_ua_logging_enabled() {
		return (1==get_option('dlm_logging_ua', 1));
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
	 * Check if visitor has downloaded version
	 *
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	public function has_ip_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE `version_id` = %d AND `user_ip` = %s AND `download_status` IN ('completed','redirected')", $version->get_id(), DLM_Utils::get_visitor_ip() ) ) ) > 0 );
	}

	/**
	 * Check if visitor has downloaded version
	 *
	 * @param DLM_Download_Version $version Version object.
	 *
	 * @return bool
	 * @since 4.6.0
	 */
	public function has_uuid_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE `version_id` = %d AND `uuid` = %s AND `download_status` IN ('completed','redirected')", $version->get_id(), DLM_Utils::get_visitor_uuid() ) ) ) > 0 );
	}

	/**
	 * Log the XHR download
	 *
	 * @return void
	 */
	public function xhr_log_download() {

		if ( ! isset( $_POST['download_id']  ) || ! isset( $_POST['version_id']  ) ) {
			if ( '1' === get_option( 'dlm_xsendfile_enabled' ) ) {
				wp_send_json_error('Missing download_id or version_id. X-Sendfile is enabled, so this is a problem.');
			}
			wp_send_json_error('Missing download_id or version_id');
		}

		// Don't log if admin hit does not need to be logged
		$admin_log = get_option( 'dlm_log_admin_download_count' );
		if ( '1' === $admin_log && is_user_logged_in() && in_array( 'administrator', wp_get_current_user()->roles, true ) ) {
			die();
		}
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		// Let's make sure the DLM_DOING_XHR is defined
		if ( ! defined( 'DLM_DOING_XHR' ) ) {
			define( 'DLM_DOING_XHR', true );
		}

		$download_id = absint( $_POST['download_id'] );
		$version_id  = absint( $_POST['version_id'] );
		$status      = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		$cookie      = 'true' === $_POST['cookie'];
		$current_url = ( isset( $_POST['currentURL'] ) ) ? esc_url_raw( $_POST['currentURL'] ) : '-';
		// Set our objects
		$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
		$version  = download_monitor()->service( 'version_repository' )->retrieve_single( $version_id );
		$download->set_version( $version );
		// Truly log the corresponding status
		$this->log( $download, $version, $status, $cookie, $current_url );
		die();
	}

	/**
	 * Create a log if logging is enabled
	 *
	 * @param string $type
	 * @param string $status
	 * @param string $message
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 * @param bool $cookie
	 * @param string $url
	 */
	public function log( $download, $version, $status = 'completed', $cookie = true, $url = '-' ) {

		if ( $this->is_count_unique_ips_only() && true === $this->has_uuid_downloaded_version( $version ) ) {
			return;
		}

		// setup new log item object.
		if ( ! DLM_Cookie_Manager::exists( $download ) ) {
			$ip       = DLM_Utils::get_visitor_ip();
			$log_item = new DLM_Log_Item();
			$log_item->set_user_id( absint( get_current_user_id() ) );
			$log_item->set_download_id( absint( $download->get_id() ) );
			$log_item->set_user_ip( $ip );
			$log_item->set_user_uuid( $ip );
			$log_item->set_user_agent( DLM_Utils::get_visitor_ua() );
			$log_item->set_version_id( absint( $version->get_id() ) );
			$log_item->set_version( $version->get_version() );
			$log_item->set_download_status( $status );
			$log_item->set_current_url( $url );

			if ( $cookie ) {
				DLM_Cookie_Manager::set_cookie( $download );
			}
			// persist log item.
			download_monitor()->service( 'log_item_repository' )->persist( $log_item );
		}
	}

	/**
	 * Failproof way to log a download if DB upgrade did not proceed correctly
	 *
	 * @param $entries
	 * @param $item
	 *
	 * @return void
	 * @since 40.6.3
	 */
	public function log_entries( $entries, $item ) {
		global $wpdb;

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'uuid' ) ) {
			$entries['uuid'] = $item->get_user_uuid();
		}

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'download_location' ) ) {
			$entries['download_location'] = $item->get_current_url();
		}

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'download_category' ) ) {
			$entries['download_category'] = $item->get_download_categories( $item->get_download_id() );
		}

		return $entries;
	}

	/**
	 * Failproof way to log a download if DB upgrade did not proceed correctly
	 *
	 * @param $entries
	 * @param $item
	 *
	 * @return void
	 * @since 4.6.3
	 */
	public function log_values( $entries ) {
		global $wpdb;

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'uuid' ) ) {
			$entries[] = '%s';
		}

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'download_location' ) ) {
			$entries[] = '%s';
		}

		if ( isset( $wpdb->download_log ) && DLM_Utils::column_checker( $wpdb->download_log, 'download_category' ) ) {
			$entries[] = '%s';
		}

		return $entries;
	}
}

