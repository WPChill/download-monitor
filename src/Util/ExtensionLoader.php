<?php

namespace WPChill\DownloadMonitor\Util;

class ExtensionLoader {

	/**
	 * Fetch static JSON string from DLM server with extension info.
	 * The response is locally stored in a transient to minimize requests.
	 *
	 * @return mixed|string
	 */
	public function fetch() {
		// Check if DLM_Product class exists
		if ( ! class_exists( '\DLM_Product' ) ) {
			require_once DLM_PLUGIN_DIR . '/src/Product/Product.php';
		}
		// Check and see if the connection to the server has failed or not.
		if ( false !== get_transient( 'dlm_extension_json_error' ) ) {
			return array( 'success' => false, 'message' => __( 'Could not connect to the Download Monitor server. Please try again later.', 'download-monitor' ) );
		}

		// Load extension json
		if ( false === ( $extension_json = get_transient( 'dlm_extension_json' ) ) ) {
			$store_url = \DLM_Product::STORE_URL;
			// Extension request
			$extension_request = wp_remote_get( 
				$store_url . '?dlm-all-extensions=true', 
				array( 'timeout' => 120 ) 
			);

			if ( ! is_wp_error( $extension_request ) ) {
				// The extension json from server
				$extension_json = wp_remote_retrieve_body( $extension_request );

				// Set Transient
				set_transient( 'dlm_extension_json', $extension_json, WEEK_IN_SECONDS );
			} else {
				// Set Transient for error, so that it won't be done again for 30 minutes
				set_transient( 'dlm_extension_json_error', 'server_error', 2 * HOUR_IN_SECONDS );
			}
		}

		return $extension_json;
	}
}
