<?php

class DLM_TC_Backwards_Compatibility {

	/**
	 * Holds the class object.
	 *
	 * @var object
	 *
	 * @since 5.0.0
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		add_filter( 'dlm_tc_cookie_access', array( $this, 'check_access' ), 30, 2 );
	}

	/**
	 * Returns the singleton instance of the class
	 *
	 * @return object The DLM_TC_Backwards_Compatibility object.
	 *
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_TC_Backwards_Compatibility ) ) {
			self::$instance = new DLM_TC_Backwards_Compatibility();
		}

		return self::$instance;
	}

	/**
	 * Check if requester has access to download
	 *
	 * @param  bool          $has_access
	 * @param  DLM_Download  $download
	 *
	 * @return bool
	 * @since 5.0.0
	 */
	public function check_access( $has_access, $download ) {
		if ( ! isset( $_COOKIE[ 'dlm_tc_access_' . $download->get_id() ] ) ) {
			$has_access = false;
		} else {
			$cookie_data = json_decode( base64_decode( $_COOKIE[ 'dlm_tc_access_' . $download->get_id() ] ), true );
			if ( empty( $cookie_data['hash'] ) || md5( $download->get_id() . DLM_Utils::get_visitor_ip() ) !== $cookie_data['hash'] ) {
				$has_access = false;
			}
		}

		return $has_access;
	}
}
