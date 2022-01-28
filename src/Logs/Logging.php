<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Logging class.
 */
class DLM_Logging {

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
	 * 60 seconds download window enabled / cookie dependant report
	 *
	 * @param [type] $download The Download object.
	 * @return bool
	 */
	public static function is_download_window_enabled( $download ) {

		if( '1' !== get_option( 'dlm_enable_window_logging' ) ) return false;

		if ( ! DLM_Cookie_Manager::exists( $download ) ) return true;

		return false;

	}

}

