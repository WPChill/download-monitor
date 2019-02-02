<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Factory {

	/**
	 * Make new order with default values (like status and currency)
	 *
	 * @return Order
	 */
	public function make() {
		$order = new Order();

		$order->set_status( Services::get()->service( 'order_status' )->get_default_status() );

		$order->set_currency( Services::get()->service( 'currency' )->get_shop_currency() );

		try {
			$order->set_date_created( new \DateTimeImmutable(current_time( 'mysql' )) );
		} catch ( \Exception $e ) {

		}

		$order->set_coupons( array() );
		$order->set_items( array() );
		$order->set_transactions( array() );


		return $order;
	}

}