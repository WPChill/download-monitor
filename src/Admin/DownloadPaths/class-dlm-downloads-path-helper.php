<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Downloads_Path_Helper class.
 *
 * @since 5.0.0
 */
class DLM_Downloads_Path_Helper {

	/**
	 * Saves the download paths if not already existing either for single site or multisite setup.
	 *
	 * @param  string $path  string of download path.
	 *
	 * @since 5.0.0
	 */
	public static function save_unique_path( $path ) {

		$saved_paths = self::get_all_paths();
		$add_file    = true;
		if ( ! empty( $save_paths ) ) {
			foreach ( $saved_paths as $save_path ) {
				if ( $path === $save_path['path_val'] ) {
					$add_file = false;
					break;
				}
			}
		}

		if ( $add_file ) {
			$lastkey       = array_key_last( $saved_paths );
			$saved_paths[] = array(
				'id'       => absint( $saved_paths[ $lastkey ]['id'] ) + 1,
				'path_val' => trailingslashit( $path ),
				'enabled'  => true,
			);
			update_option( 'dlm_allowed_paths', $saved_paths );
		}
	}

	/**
	 * Saves the download paths either for single site or multisite setup.
	 * In case of multisite, the blog should be switched to the desired blog before calling this function.
	 *
	 * @param  array $paths  Array of download paths.
	 *
	 * @since 5.0.0
	 */
	public static function save_paths( $paths ) {
		update_option( 'dlm_allowed_paths', $paths );
	}

	/**
	 * Retrieves all download paths either for single site or multisite setup.
	 * In case of multisite, the blog should be switched to the desired blog before calling this function.
	 *
	 * @return array Array of download paths.
	 * @since 5.0.0
	 */
	public static function get_all_paths() {
		if ( is_multisite() ) {
			return get_option( 'dlm_allowed_paths' );
		}

		$option = get_option( 'dlm_allowed_paths' );
		// Check if it's string & do compatibility for < 5.0.0
		if ( is_string( $option ) ) {
			if ( '' !== $option ) {
				// Not empty string, save as new format since 5.0.0
				$paths = array(
					array(
						'id'       => 1,
						'path_val' => $option,
						'enabled'  => true,
					),
				);
				self::save_paths( $paths );

				return $paths;
			}

			return array();
		}

		return $option;
	}

	/**
	 * Retrieves all allowed download paths.
	 *
	 * @return array Array of allowed download paths.
	 * @since 5.0.0
	 */
	public static function get_allowed_paths() {
		$user_paths = DLM_Downloads_Path_Helper::get_all_paths();
		$return     = array();

		if ( ! empty( $user_paths ) && is_array( $user_paths ) ) {
			foreach ( $user_paths as $user_path ) {
				if ( isset( $user_path['enabled'] ) && $user_path['enabled'] ) {
					$return[] = str_replace( DIRECTORY_SEPARATOR, '/', $user_path['path_val'] );
				}
			}
		}

		return $return;
	}

	/**
	 * Retrieves the base admin URL for the download settings.
	 *
	 * @return string Base admin URL.
	 * @since 5.0.0
	 */
	public static function get_base_url() {
		return add_query_arg(
			array(
				'post_type' => 'dlm_download',
				'page'      => 'download-monitor-settings',
				'tab'       => 'advanced',
				'section'   => 'download_path',
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Enables a download path.
	 *
	 * @param  string $path  The path string.
	 *
	 * @since 5.0.0
	 */
	public static function enable_download_path( $path ) {
		$paths = DLM_Downloads_Path_Helper::get_all_paths();
		foreach ( $paths as $key => $a_path ) {
			if ( rtrim( str_replace( DIRECTORY_SEPARATOR, '/', $a_path['path_val'] ), "/\\" ) === rtrim( str_replace( DIRECTORY_SEPARATOR, '/', $path ), "/\\" ) ) {
				$paths[ $key ]['enabled'] = true;
				self::save_paths( $paths );
				break;
			}
		}
	}

	/**
	 * Disables a download path.
	 *
	 * @param  string $path  The path string.
	 *
	 * @since 5.0.0
	 */
	public static function disable_download_path( $path ) {
		$paths = self::get_all_paths();
		foreach ( $paths as $key => $a_path ) {
			if ( str_replace( DIRECTORY_SEPARATOR, '/', $a_path['path_val'] ) === str_replace( DIRECTORY_SEPARATOR, '/', $path ) ) {
				$paths[ $key ]['enabled'] = false;
				self::save_paths( $paths );
				break;
			}
		}
	}
}
