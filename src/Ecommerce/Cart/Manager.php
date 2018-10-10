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

		$subtotal      = 0;
		$tax_total     = 0;
		$coupons_total = 0;

		/**
		 * Set items
		 */
		$session_items = $session->get_items();
		$items         = array();
		if ( ! empty( $session_items ) ) {
			/** @var Session\Item\Item $session_item */
			foreach ( $session_items as $session_item ) {
				$item = new Item();
				$item->set_qty( $session_item->get_qty() );
				try {
					/** @var \Never5\DownloadMonitor\Ecommerce\DownloadProduct\DownloadProduct $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $session_item->get_download_id() );
					$item->set_label( $download->get_title() );
					$item->set_subtotal( $download->get_price() );

					/** @todo [TAX] Implement taxes */
					$item->set_tax_total( 0 );
					$item->set_total( $download->get_price() );

					// add item to items array
					$items[] = $item;

					// add this price to sub total
					$subtotal += $download->get_price();
				} catch ( Exception $exception ) {
					// no download with ID 4 found
				}
			}
		}
		$cart->set_items( $items );


		/**
		 * Set sub total
		 */
		$cart->set_subtotal( $subtotal );

		/**
		 * Set tax total
		 */
		$cart->set_tax_total( $tax_total );

		/**
		 * Set total
		 */
		$cart->set_total( ( $subtotal + $tax_total ) - $coupons_total );

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