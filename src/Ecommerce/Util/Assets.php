<?php

namespace Never5\DownloadMonitor\Ecommerce\Util;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Assets {

	/**
	 * Setup hook
	 */
	public function setup() {
		add_action( 'dlm_frontend_scripts_after', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets
	 */
	function enqueue_assets() {

		if ( Services::get()->service( 'page' )->is_cart() ) {
			wp_enqueue_style( 'dlm-frontend-cart', download_monitor()->get_plugin_url() . '/assets/css/cart.css' );
		}

	}

}