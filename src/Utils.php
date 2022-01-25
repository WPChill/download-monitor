<?php

/**
 * DLM_Utils
 *
 * Modified @since 4.5.0
 */
abstract class DLM_Utils {

	/**
	 * Local independent basename
	 *
	 * @param string $filepath
	 *
	 * @return string
	 */
	public static function basename( $filepath ) {
		return preg_replace( '/^.+[\\\\\\/]/', '', $filepath );
	}

	/**
	 * Check for existing table
	 *
	 * @param string $table The table we are checking.
	 *
	 * @return bool
	 */
	public static function table_checker( $table ) {
		global $wpdb;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {

			return true;

		}

		return false;
	}
}
