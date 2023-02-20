<?php

/**
 * DLM_Utils
 *
 * Modified @since 4.5.0
 */
abstract class DLM_Utils {

	/**
	 * Defined tables
	 *
	 * @var array $tables An array of defined tables.
	 *
	 * @since 4.7.75
	 */
	private static $tables = array();
	/**
	 * Get visitor's IP address
	 *
	 * @return string
	 */
	public static function get_visitor_ip() {

		// Fix for CloudFlare IPs
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ){
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( isset( $_SERVER["HTTP_X_REAL_IP"] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER["HTTP_X_REAL_IP"] ) );
		}

		if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER["HTTP_CF_CONNECTING_IP"] ) );
		}
		
		if (  ( '1' == get_option( 'dlm_allow_x_forwarded_for', 0 ) ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// phpcs:ignore
			$parts = explode( ",", $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip    = trim( array_shift( $parts ) );
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Get visitor's user agent
	 *
	 * @return string
	 */
	public static function get_visitor_ua() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ;

		if ( strlen( $ua ) > 200 ) {
			$ua = substr( $ua, 0, 199 );
		}

		return $ua;
	}

	/**
	 * Check if a given ip is in a network (IPv4)
	 * https://gist.github.com/tott/7684443
	 *
	 * @param  string $ip    IP to check in IPv4 format eg. 127.0.0.1
	 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
	 * @return boolean true if the ip is in this range / false if not.
	 */
	public static function ipv4_in_range( $ip, $range ) {
		if ( strpos( $range, '/' ) == false ) {
			$range .= '/32';
		}

		list( $range, $netmask ) = explode( '/', $range, 2 );
		$range_decimal = ip2long( $range );
		$ip_decimal = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal = ~ $wildcard_decimal;
		return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
	}

	/**
	 * Helper function for ipv6_in_range()
	 * Converts inet_pton output to string with bits
	 */
	private static function inet_to_bits( $inet ) {
		$unpacked = unpack( 'A16', $inet );
		$unpacked = str_split( $unpacked[1] );
		$binaryip = '';

		foreach ( $unpacked as $char ) {
			$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		return $binaryip;
	}

	/**
	 * Check if a given ip is in a network (IPv6)
	 * http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
	 *
	 * @param  string $ip    IP to check in IPv6 format eg. 2001:db8::1
	 * @param  string $range IP/CIDR netmask eg. 2001:db8::/32, also 2001:db8::1 is accepted and /128 assumed
	 * @return boolean true if the ip is in this range / false if not.
	 */
	public static function ipv6_in_range( $ip, $range ) {
		// Windows didn't get inet_pton until PHP 5.3.0
		if ( ! function_exists( 'inet_pton' ) ) {
			return false;
		}

		if ( strpos( $range, '/' ) == false ) {
			$range .= '/128';
		}

		$ip = inet_pton( $ip );
		$binaryip = self::inet_to_bits( $ip );

		list( $net, $maskbits ) = explode( '/', $range, 3 );
		$net = inet_pton( $net );
		$binarynet = self::inet_to_bits( $net );

		$ip_net_bits = substr( $binaryip, 0, $maskbits );
		$net_bits = substr( $binarynet, 0, $maskbits );

		return ( $ip_net_bits === $net_bits );
	}

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
	 * Retrieves the longes common substring from a list of strings
	 *
	 * @param array $file_paths
	 * 
	 * @return string Longest common substring
	 * @since 4.5.92
	 */
	public static function longest_common_path( $file_paths ) {

		$paths     = array();

		for ( $i = 0; $i < count( $file_paths ); $i++ ) {
			$paths[ $i ] = explode( DIRECTORY_SEPARATOR, $file_paths[ $i ] );
		}

		array_multisort( array_map( 'count', $paths ), SORT_ASC, $paths );

		$count        = min( array_map( 'count', $paths ) );
		$common_parts = array();
		// If there is only 1 path it means it is a standard WordPress installation, so return standard ABSPATH path
		if ( 1 === count( $file_paths ) ) {
			return $file_paths[0];
		}

		// The other 2 scenarios we have are with 2 or 3 paths, where the WP_CONTENT_DIR and ABPSPATH have different paths
		// Plus the scenario where the user has included another path for the downloads
		for ( $i = 0; $i < $count; $i++ ) {
			if ( $paths[0][ $i ] === $paths[1][ $i ] ) {
				$common_parts[] = $paths[0][ $i ];
			} else {
				break;
			}
		}

		if ( isset( $paths[2] ) ) {
			$new_path       = implode( DIRECTORY_SEPARATOR, $common_parts );
			$last_file_path = implode( DIRECTORY_SEPARATOR, $paths[2] );

			if ( ! empty( $new_path ) && false !== strpos( $last_file_path, $new_path ) ) {
				return $new_path;
			} else {
				$max = count( $common_parts );
				for ( $i = $max - 1; $i > 0; $i-- ) {
					if ( $common_parts[ $i ] !== $paths[2][ $i ] ) {
						unset( $common_parts[ $i ] );
					} else {
						break;
					}
				}
			}
		}

		return implode( DIRECTORY_SEPARATOR, $common_parts );

	}

	/**
	 *Check for existing table
	 *
	 * @param string $table The table we are checking.
	 *
	 * @return bool
	 */
	public static function table_checker( $table ) {

		if ( empty( self::$tables ) || ! in_array( $table, self::$tables ) ) {
			global $wpdb;
			// If exists, return true.
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {

				self::$tables[] = $table;

				return true;
			}
			// Doesn't exist, return false.
			return false;
		}
		// Exists in variable, return true.
		return true;
	}


	/**
	 * Check for existing column inside a table
	 *
	 * @param string $table_name The table in witch we are checking.
	 *
	 * @param string $col_name The column we are checking for.
	 *
	 * @return bool
	 */
	public static function column_checker( $table_name, $col_name ) {
		global $wpdb;
	 
		$diffs   = 0;
		$results = $wpdb->get_results( "DESC $table_name" );
	 
		foreach ( $results as $row ) {
	 
			if ( $row->Field === $col_name ) {
	 
				return true;
			} // End if found our column.
		}
	 
		return false;
	}

	/*
	*
	* Generate html attributes based on array
	*
	* @param array atributes
	*
	* @return string
	* @since 4.6.0
	*
	*/
	public static function generate_attributes( $attributes ) {
		$return = '';

		// Let's unset the inner_html attribute so that it doesn't end up in our attributes
		if ( isset( $attributes['inner_html'] ) ) {
			unset( $attributes['inner_html'] );
		}

		foreach ( $attributes as $name => $value ) {

			if ( is_array( $value ) && 'class' == $name ) {
				$value = implode( ' ', $value );
			}elseif ( is_array( $value ) ) {
				$value = json_encode( $value );
			}

			if ( in_array( $name, array( 'alt', 'rel', 'title' ) ) ) {
				$value = str_replace( '<script', '&lt;script', $value );
				$value = strip_tags( htmlspecialchars( $value ) );
				$value = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $value );
			}

			$return .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return $return;

	}

	/**
	 * Get visitor UUID
	 *
	 * @return string
	 * @since 4.6.0
	 */
	public static function get_visitor_uuid() {
		return md5( self::get_visitor_ip() );
	}

	/**
	 * Fix for WPML setting language for download links. IF the downloads are made trasnlatable this will need to be deleted.
	 *
	 * @param string $home_url Home URL made by WPML.
	 * @param string $url Original URL.
	 *
	 * @return string $home_url The correct home URL for Download Monitor.
	 */
	public static function wpml_download_link( $home_url, $url ) {

		return $url;
	}

}
