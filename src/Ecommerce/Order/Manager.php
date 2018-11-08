<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Manager {

	/**
	 * Get available statuses
	 *
	 * @return array
	 */
	public function get_available_statuses() {
		return apply_filters( 'dlm_ecommerce_order_statuses', array(
			'pending-payment',
			'completed',
			'failed',
			'refunded'
		) );
	}

	/**
	 * Get the default order status
	 *
	 * @return string
	 */
	public function get_default_status() {
		return apply_filters( 'dlm_ecommerce_default_order_status', 'pending-payment' );
	}

	/**
	 * Build an array with OrderItem objects based on items in current cart
	 *
	 * @return OrderItem[]
	 */
	public function build_order_items_from_cart() {

		$order_items = array();

		/** @var \Never5\DownloadMonitor\Ecommerce\Cart\Cart $cart */
		$cart = Services::get()->service( 'cart' )->get_cart();

		$cart_items = $cart->get_items();

		if ( ! empty( $cart_items ) ) {
			/** @var \Never5\DownloadMonitor\Ecommerce\Cart\Item\Item $cart_item */
			foreach ( $cart_items as $cart_item ) {
				$order_item = new OrderItem();

				$order_item->set_label( $cart_item->get_label() );
				$order_item->set_qty( $cart_item->get_qty() );
				$order_item->set_download_id( $cart_item->get_download_id() );
				$order_item->set_subtotal( $cart_item->get_subtotal() );
				$order_item->set_tax_total( $cart_item->get_tax_total() );
				/** @todo set tax class */
				$order_item->set_total( $cart_item->get_total() );

				$order_items[] = $order_item;
			}
		}

		return $order_items;

	}

}