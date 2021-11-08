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
}
