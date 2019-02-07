<?php

class DLM_PHPVersion {

	/**
	 * This method check if the installed PHP version is high enough to run the ecommerce component of DLM.
	 * Currently PHP 5.3 or higher is required.
	 *
	 * @return bool
	 */
	public static function is_shop_ready() {
		if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
			return true;
		}

		return false;
	}

}