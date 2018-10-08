<?php

namespace Never5\DownloadMonitor\Ecommerce\Cart;

use Never5\DownloadMonitor\Ecommerce\Services\Services;
use Never5\DownloadMonitor\Ecommerce\Session;

class Manager {

	/**
	 * @param Session\Session $session
	 *
	 * @return Cart
	 */
	private function build_cart_from_session( $session ) {
		$cart = new Cart();

		return $cart;
	}


	/**
	 * Get current cart.
	 * This method builds the current cart based on the user session.
	 *
	 * @return Cart
	 */
	public function get_cart() {

		// get current session from cookie
		$session = Services::get()->service( 'session' )->get_session();

		// build a cart object from given session
		return $this->build_cart_from_session( $session );
	}


}