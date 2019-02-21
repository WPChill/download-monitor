<?php

namespace Never5\DownloadMonitor\Tests\Order;

use Never5\DownloadMonitor\Shop\Services\Services;

require_once 'TestOrder.php';

class WordPressRepository extends \DLM_Unit_Test_Case {
	
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