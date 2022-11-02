<?php

class DLM_Cookie_Manager {

	const KEY = 'wp_dlm_downloading';

	/**
	 * Check if the cookie is exists for this download & version. If it does exists the requester has requested the exact same download & version in the past minute.
	 *
	 * @param DLM_Download $download
	 *
	 * @return bool
	 */
	public static function exists( $download ) {
		$exists = false;

		// get JSON data
		$cdata = self::get_cookie_data();

		// check if no parse errors occurred
		if ( null != $cdata && is_array( $cdata ) && ! empty( $cdata ) ) {

			// check in cookie data for download AND version ID
			if ( $cdata['download'] == $download->get_id() && $cdata['version'] == $download->get_version()->get_version_number() ) {
				$exists = true;
			}
		}


		return $exists;
	}

	/**
	 * Get cookie data
	 *
	 * @return array|null
	 */
	public static function get_cookie_data() {
		$cdata = null;
		if ( ! empty( $_COOKIE[ self::KEY ] ) ) {
			$cdata = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_COOKIE[ self::KEY ] ) ) ), true );
		}

		return $cdata;
	}

	/**
	 * Set cookie
	 *
	 * @param DLM_Download $download
	 */
	public static function set_cookie( $download ) {
		$secure      = is_ssl();
		$cookie_data = apply_filters(
			'wp_dlm_set_downloading_cookie',
			array(
				'expires'  => time() + 60,
				'secure'   => $secure,
				'httponly' => true
			)
		);
		setcookie(
			self::KEY,
			base64_encode(
				json_encode(
					array(
						'download' => $download->get_id(),
						'version'  => $download->get_version()->get_version_number()
					)
				)
			),
			$cookie_data['expires'],
			COOKIEPATH,
			COOKIE_DOMAIN,
			$cookie_data['secure'],
			$cookie_data['httponly']
		);
	}
}
