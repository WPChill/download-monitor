<?php

use \WPChill\DownloadMonitor\Util;

class DLM_Test_PageCreator extends DLM_Unit_Test_Case {

	/**
	 * Test create_no_access_page()
	 */
	public function test_create_no_access_page() {
		$pc = new Util\PageCreator();

		$na_id = $pc->create_no_access_page();

		$this->assertNotEquals( 0, $na_id );

		$na_option_id = download_monitor()->service( 'settings' )->get_option( 'no_access_page' );

		$this->assertEquals( $na_id, $na_option_id );
	}

	/**
	 * Test create_cart_page()
	 */
	public function test_create_cart_page() {
		$pc = new Util\PageCreator();

		$cart_id = $pc->create_cart_page();

		$this->assertNotEquals( 0, $cart_id );

		$cart_option_id = download_monitor()->service( 'settings' )->get_option( 'page_cart' );

		$this->assertEquals( $cart_id, $cart_option_id );
	}

	/**
	 * Test create_checkout_page()
	 */
	public function test_create_checkout_page() {
		$pc = new Util\PageCreator();

		$checkout_id = $pc->create_checkout_page();

		$this->assertNotEquals( 0, $checkout_id );

		$checkout_option_id = download_monitor()->service( 'settings' )->get_option( 'page_checkout' );

		$this->assertEquals( $checkout_id, $checkout_option_id );
	}

	/**
	 * Test create_no_access_page()
	 */
	public function test_create_existing_page() {
		$pc = new Util\PageCreator();

		$na_id = $pc->create_no_access_page();

		$this->assertNotEquals( 0, $na_id );

		$na_id_second = $pc->create_no_access_page();

		$this->assertEquals( $na_id, $na_id_second );
	}

}