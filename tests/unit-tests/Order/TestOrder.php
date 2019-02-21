<?php

namespace Never5\DownloadMonitor\Tests\Order;

use Never5\DownloadMonitor\Shop\Order;
use Never5\DownloadMonitor\Shop\Services\Services;

/**
 * Class TestOrder
 * @package Never5\DownloadMonitor\Tests\Order
 *
 * An order object with fake data, used for testing. Note that object when creating only exists in memory (not in DB).
 */
class TestOrder {

	/**
	 * Create an Order object for testing
	 *
	 * @return Order\Order
	 */
	public static function make() {

		/** @var \Never5\DownloadMonitor\Shop\Order\Order $order */
		$order = Services::get()->service( "order_factory" )->make();

		/** Set customer */
		$order->set_customer( new Order\OrderCustomer(
			"TestFirstName",
			"TestLastName",
			"TestCompany",
			"TestAddress1",
			"TestAddress2",
			"TestCity",
			"TestState",
			"TestPostcode",
			"NL",
			"TestEmail",
			"TestPhone",
			"TestIPAddress"
		) );

		/** Add a fake order item */
		$order_item = new Order\OrderItem();
		$order_item->set_label( "A test Item" );
		$order_item->set_qty( 1 );
		$order_item->set_download_id( 1 );
		$order_item->set_subtotal( 999 );
		$order_item->set_tax_total( 0 );
		$order_item->set_total( 999 );
		$order->set_items( array( $order_item ) );

		/** Add a fake transaction */

		/** @var \Never5\DownloadMonitor\Shop\Order\Transaction\OrderTransaction $dlm_transaction */
		$dlm_transaction = Services::get()->service( 'order_transaction_factory' )->make();
		$dlm_transaction->set_amount( $order->get_total() );
		$dlm_transaction->set_processor( "UnitTest" );
		$dlm_transaction->set_processor_nice_name( "Unit Test Gateway" );
		$dlm_transaction->set_processor_transaction_id( 'TEST_ID' );
		$dlm_transaction->set_processor_status( 'approved' );
		$dlm_transaction->set_status( Services::get()->service( 'order_transaction_factory' )->make_status( 'success' ) );

		// add transaction to order
		$order->add_transaction( $dlm_transaction );

		return $order;
	}


}