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

		// Load extension json
		if ( false === ( $extension_json = get_transient( 'dlm_extension_json' ) ) ) {
			$store_url = \DLM_Product::STORE_URL;
			// Extension request
			$extension_request = wp_remote_get( $store_url . '?dlm-all-extensions=true' );

			if ( ! is_wp_error( $extension_request ) ) {

				// The extension json from server
				$extension_json = wp_remote_retrieve_body( $extension_request );

				// Set Transient
				set_transient( 'dlm_extension_json', $extension_json, WEEK_IN_SECONDS );
			}
		}

		return $extension_json;
	}
}
