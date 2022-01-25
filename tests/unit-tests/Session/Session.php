<?php

namespace WPChill\DownloadMonitor\Tests\Session;

use WPChill\DownloadMonitor\Shop\Services\Services;

class Session extends \DLM_Unit_Test_Case {

	/**
	 * Test set_key() and get_key()
	 */
	public function test_key() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$new_key = "testing";

		$session->set_key( $new_key );

		$this->assertEquals( $session->get_key(), $new_key );
	}

	/**
	 * Test set_key() and get_key()
	 */
	public function test_hash() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$new_hash = "testing";

		$session->set_hash( $new_hash );

		$this->assertEquals( $session->get_hash(), $new_hash );
	}

	/**
	 * Test set_key() and get_key()
	 */
	public function test_expiry() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$new_expiry = new \DateTimeImmutable();
		$new_expiry = $new_expiry->add( new \DateInterval( 'PT45M' ) );

		$session->set_expiry( $new_expiry );

		$this->assertEquals( $session->get_expiry(), $new_expiry );
	}

	/**
	 * Test add_coupon()
	 */
	public function test_add_coupon() {
		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();


		$this->assertCount( 0, $session->get_coupons() );

		$session->add_coupon( "test" );

		$this->assertCount( 1, $session->get_coupons() );
	}

	/**
	 * Test remove_coupon()
	 */
	public function test_remove_coupon() {
		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$this->assertCount( 0, $session->get_coupons() );

		$session->add_coupon( "test" );

		$this->assertCount( 1, $session->get_coupons() );

		$session->remove_coupon( "test" );

		$this->assertCount( 0, $session->get_coupons() );
	}

	/**
	 * Test add_item()
	 */
	public function test_add_item() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$this->assertCount( 0, $session->get_items() );

		$item = Services::get()->service( 'session_item_factory' )->make( 1, 1 );
		$session->add_item( $item );

		$this->assertCount( 1, $session->get_items() );
	}

	/**
	 * Test add_item()
	 */
	public function test_remove_item() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		$this->assertCount( 0, $session->get_items() );

		/** @var \WPChill\DownloadMonitor\Shop\Session\Item\Item $item */
		$item = Services::get()->service( 'session_item_factory' )->make( 1, 1 );
		$session->add_item( $item );

		$this->assertCount( 1, $session->get_items() );

		$session->remove_item( $item->get_key() );

		$this->assertCount( 0, $session->get_items() );
	}
}