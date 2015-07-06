<?php

abstract class DLM_Utils {

	/**
	 * Get visitor's IP address
	 *
	 * @return string
	 */
	public static function get_visitor_ip() {
		return sanitize_text_field( ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'] );
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

}