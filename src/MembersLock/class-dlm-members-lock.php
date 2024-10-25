<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DLM_Members_Lock
 *
 * Class to handle the Members lock system
 *
 * @since 5.0.13
 */
class DLM_Members_Lock {

	/**
	 * Class constructor.
	 *
	 * @since 5.0.13
	 */
	public function __construct() {
		// Download Access Manager.
		$access_manager = new DLM_Members_Access_Manager();
		$access_manager->setup();
		// No Access Modal
		$modal = new DLM_Members_Modal();
	}

	/**
	 * Get the plugin file
	 *
	 * @static
	 *
	 * @return String
	 *
	 * @since 5.0.13
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}
}
