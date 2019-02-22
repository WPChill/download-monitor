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
	 * Test retrieve() with filters
	 */
	public function test_retrieve_filtered() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		/** @var \Never5\DownloadMonitor\Shop\Order\Status\Factory $osf */
		$osf = Services::get()->service( 'order_status_factory' );

		// store first dummy order (status pending-payment)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'pending-payment' ) );
		$repo->persist( $order );

		// store second dummy order (status completed)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'completed' ) );
		$repo->persist( $order );

		// retrieve all orders with status completed
		$orders = $repo->retrieve( array(
			array( 'key' => 'status', 'value' => 'completed' )
		) );

		$this->assertCount( 1, $orders );
		$this->assertEquals( 'completed', $orders[0]->get_status()->get_key() );
	}

	/**
	 * Test retrieve() with a limit and an offset
	 */
	public function test_retrieve_offset() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		/** @var \Never5\DownloadMonitor\Shop\Order\Status\Factory $osf */
		$osf = Services::get()->service( 'order_status_factory' );

		// store first dummy order (status pending-payment)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'pending-payment' ) );
		$repo->persist( $order );

		// store second dummy order (status completed)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'completed' ) );
		$repo->persist( $order );

		// set offset to 1, no limit to let repo fix this
		$orders = $repo->retrieve( array(), 1, 1, 'id', 'ASC' );

		$this->assertCount( 1, $orders );
		$this->assertEquals( 'completed', $orders[0]->get_status()->get_key() );
	}

	/**
	 * Test retrieve() with an offset and no limit
	 */
	public function test_retrieve_offset_no_limit() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		/** @var \Never5\DownloadMonitor\Shop\Order\Status\Factory $osf */
		$osf = Services::get()->service( 'order_status_factory' );

		// store first dummy order (status pending-payment)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'pending-payment' ) );
		$repo->persist( $order );

		// store second dummy order (status completed)
		$order = TestOrder::make();
		$order->set_status( $osf->make( 'completed' ) );
		$repo->persist( $order );

		// set offset to 1, no limit to let repo fix this
		$orders = $repo->retrieve( array(), 0, 1, 'id', 'ASC' );

		$this->assertCount( 1, $orders );
		$this->assertEquals( 'completed', $orders[0]->get_status()->get_key() );
	}

	/**
	 * Test retrieve() on order that has a modified date
	 */
	public function test_retrieve_with_date_modified() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// create dummy order
		$order = TestOrder::make();

		// set modified date 45 minutes later
		$dm = $order->get_date_created()->add( new \DateInterval( "PT45M" ) );
		$order->set_date_modified( $dm );

		// persist order
		$repo->persist( $order );

		// retrieve all orders
		$orders = $repo->retrieve();

		$this->assertCount( 1, $orders );

		$this->assertEquals( $dm, $orders[0]->get_date_modified() );
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
	 * Test retrieve_single() on order id that doesn't exist
	 */
	public function test_retrieve_single_not_found() {
		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );
		$this->expectException( \Exception::class );
		$repo->retrieve_single( 1 );
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
	 * Test persist() on an existing order
	 */
	public function test_persist_update() {

		/** @var \Never5\DownloadMonitor\Shop\Order\WordPressRepository $repo */
		$repo = Services::get()->service( "order_repository" );

		// create dummy order
		$order = TestOrder::make();

		// set name to Barry
		$order->get_customer()->set_first_name( "Barry" );

		// persist order
		$repo->persist( $order );

		// retrieve order from DB
		$db_order = $repo->retrieve_single( $order->get_id() );

		// check if name is still Barry
		$this->assertEquals( "Barry", $db_order->get_customer()->get_first_name() );

		// set name to Lucas
		$db_order->get_customer()->set_first_name( "Lucas" );

		// update order
		$repo->persist( $db_order );

		// retrieve order from DB
		$db_order = $repo->retrieve_single( $order->get_id() );

		// check if name is now Lucas
		$this->assertEquals( "Lucas", $db_order->get_customer()->get_first_name() );
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