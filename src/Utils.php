<?php

abstract class DLM_Utils {

	/**
	 * Get visitor's IP address
	 *
	 * @return string
	 */
	public static function get_visitor_ip() {

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

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
		return preg_replace('/^.+[\\\\\\/]/', '', $filepath);
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
		$real_path = '';

		for ( $i = 0; $i < count( $file_paths ); $i++ ) {
			$paths[ $i ] = explode( DIRECTORY_SEPARATOR, $file_paths[ $i ] );
		}

		array_multisort( array_map( 'count', $paths ), SORT_ASC, $paths );

		$count  = min( array_map( 'count', $paths ) );
		$passed = 0;
		$n      = count( $paths );
		// If there is only 1 path it means it is a standard WordPress installation, so return standard ABSPATH path
		if ( 1 === count( $file_paths ) ) {
			return $file_paths[0];
		}

		// The other 2 scenarios we have are with 2 or 3 paths, where the WP_CONTENT_DIR and ABPSPATH have different paths
		// Plus the scenario where the user has included another path for the downloads
		while ( $count > $passed ) {
			for ( $i = 0; $i < $n ; $i++ ) {
				if ( $n > 2 ) {
					if ( isset( $paths[ $i + 1 ] ) && isset( $paths[ $i + 2 ] ) && $paths[ $i ][ $passed ] === $paths[ $i + 1 ][ $passed ] && $paths[ $i ][ $passed ] === $paths[ $i + 2 ][ $passed ] ) {
						$real_path .= $paths[ $i ][ $passed ] . DIRECTORY_SEPARATOR;
						break;
					}
				} else {
					if ( isset( $paths[ $i + 1 ] ) && $paths[ $i ][ $passed ] === $paths[ $i + 1 ][ $passed ] ) {
						$real_path .= $paths[$i ][ $passed ] . DIRECTORY_SEPARATOR;
						break;
					}
				}
			}
			$passed++;
			$count--;
		}

		return $real_path;

	}

}