<?php

namespace Never5\DownloadMonitor\Tests\Order;

use Never5\DownloadMonitor\Shop\Services\Services;

require_once 'TestOrder.php';

class WordPressRepository extends \DLM_Unit_Test_Case {

	/**
	 * Test retrieve()
	 */
	public function test_retrieve() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// create dummy order
		$order = TestOrder::make();

		// persist order
		$repo->persist( $order );

		// retrieve all orders
		$orders = $repo->retrieve();

		$this->assertCount( 1, $orders );

		$this->assertEquals( $order->get_total(), $orders[0]->get_total() );
	}

	/**
	 * Test retrieve_single()
	 */
	public function test_retrieve_single() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// create dummy order
		$order = TestOrder::make();

		// persist order
		$repo->persist( $order );

		// retrieve all orders
		$db_order = $repo->retrieve_single( $order->get_id() );

		$this->assertEquals( $order->get_id(), $db_order->get_id() );
		$this->assertEquals( $order->get_status()->get_key(), $db_order->get_status()->get_key() );
	}

	/**
	 * Test persist()
	 */
	public function test_persist() {

		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// check if we're starting clean
		$this->assertEquals( 0, $repo->num_rows() );

		// create dummy order
		$order = TestOrder::make();

		// persist order
		$repo->persist( $order );

		// check for 0 orders in DB
		$this->assertEquals( 1, $repo->num_rows() );
	}

	/**
	 * Test delete()
	 */
	public function test_delete() {

		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// check if we're starting clean
		$this->assertEquals( 0, $repo->num_rows() );

		// create dummy order
		$order = TestOrder::make();

		// persist order
		$repo->persist( $order );

		// delete order
		$repo->delete( $order->get_id() );

		// check for 0 orders in DB
		$this->assertEquals( 0, $repo->num_rows() );
	}
}