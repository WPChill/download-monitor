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

			if ( false !== strpos( $last_file_path, $new_path ) ) {
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
		global $wpdb;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {

			return true;

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

}
