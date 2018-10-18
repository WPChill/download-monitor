<?php

namespace Never5\DownloadMonitor\Ecommerce\Util;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Redirect {

	/**
	 * Internal redirect method
	 *
	 * @param string $url
	 */
	private function redirect( $url ) {
		wp_redirect( $url );
		exit;
	}

	/**
	 * Redirect to checkout page
	 */
	public function to_checkout() {
		$this->redirect( Services::get()->service( 'page' )->get_checkout_url() );
	}

	/**
	 * Redirect to cart page
	 */
	public function to_cart() {
		$this->redirect( Services::get()->service( 'page' )->get_cart_url() );
	}
}