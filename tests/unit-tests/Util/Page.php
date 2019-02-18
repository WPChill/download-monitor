<?php

use \Never5\DownloadMonitor\Shop\Util;
use Never5\DownloadMonitor\Shop\Services\Services;

class DLM_Test_Page extends DLM_Unit_Test_Case {

	/**
	 * Test is_cart()
	 */
	public function test_is_cart() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create other page
		$other_id = $this->factory()->post->create(
			array( 'post_title' => 'Not a cart', 'post_content' => 'Dummy', 'post_type' => 'page' )

		);

		// create cart page
		$cart_id = $this->factory()->post->create(
			array( 'post_title' => 'Cart', 'post_content' => '[dlm_cart]', 'post_type' => 'page' )

		);

		// set cart ID
		update_option( 'dlm_page_cart', $cart_id );

		// fake going to cart page
		$this->go_to( get_permalink( $cart_id ) );

		// we are on cart page now
		$this->assertTrue( $pageUtil->is_cart() );

		// go to other page
		$this->go_to( get_permalink( $other_id ) );

		// we are not on cart page now
		$this->assertFalse( $pageUtil->is_cart() );
	}

	/**
	 * Test get_cart_url()
	 */
	public function test_get_cart_url() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create cart page
		$cart_id = $this->factory()->post->create(
			array( 'post_title' => 'Cart', 'post_content' => '[dlm_cart]', 'post_type' => 'page' )

		);

		// set cart ID
		update_option( 'dlm_page_cart', $cart_id );

		// Check cart URL
		$this->assertEquals( get_permalink( $cart_id ), $pageUtil->get_cart_url() );
	}

	/**
	 * Test get_add_to_cart_url()
	 */
	public function test_get_add_to_cart_url() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create cart page
		$cart_id = $this->factory()->post->create(
			array( 'post_title' => 'Cart', 'post_content' => '[dlm_cart]', 'post_type' => 'page' )

		);

		// set cart ID
		update_option( 'dlm_page_cart', $cart_id );

		// Check add to cart URL
		$this->assertEquals( add_query_arg( 'dlm-add-to-cart', 1234, get_permalink( $cart_id ) ), $pageUtil->get_add_to_cart_url( 1234 ) );
	}

	/**
	 * Test is_checkout()
	 */
	public function test_is_checkout() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create other page
		$other_id = $this->factory()->post->create(
			array( 'post_title' => 'Not checkout', 'post_content' => 'Dummy', 'post_type' => 'page' )

		);

		// create cart page
		$checkout_id = $this->factory()->post->create(
			array( 'post_title' => 'Checkout', 'post_content' => '[dlm_checkout]', 'post_type' => 'page' )

		);

		// set cart ID
		update_option( 'dlm_page_checkout', $checkout_id );

		// fake going to cart page
		$this->go_to( get_permalink( $checkout_id ) );

		// we are on cart page now
		$this->assertTrue( $pageUtil->is_checkout() );

		// go to other page
		$this->go_to( get_permalink( $other_id ) );

		// we are not on cart page now
		$this->assertFalse( $pageUtil->is_checkout() );
	}

	/**
	 * Test get_checkout_url()
	 */
	public function test_get_checkout_url() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create checkout page
		$checkout_id = $this->factory()->post->create(
			array( 'post_title' => 'Checkout', 'post_content' => '[dlm_checkout]', 'post_type' => 'page' )

		);

		// set checkout ID
		update_option( 'dlm_page_checkout', $checkout_id );

		// check normal checkout URL
		$this->assertEquals( get_permalink( $checkout_id ), $pageUtil->get_checkout_url() );

		// check different endpoint checkout URLs
		$this->assertEquals( add_query_arg( 'ep', 'complete', get_permalink( $checkout_id ) ), $pageUtil->get_checkout_url( 'complete' ) );
		$this->assertEquals( add_query_arg( 'ep', 'cancelled', get_permalink( $checkout_id ) ), $pageUtil->get_checkout_url( 'cancelled' ) );
		$this->assertEquals( get_permalink( $checkout_id ), $pageUtil->get_checkout_url( 'nonexisting' ) );
	}

	/**
	 * Test is_cart()
	 */
	public function test_to_cart() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create cart page
		$cart_id = $this->factory()->post->create(
			array( 'post_title' => 'Cart', 'post_content' => '[dlm_cart]', 'post_type' => 'page' )
		);

		// set cart ID
		update_option( 'dlm_page_cart', $cart_id );

		// redirect to cart via Util\Redirect
		$pageUtil->to_cart();

		// we are on cart page now
		$this->assertTrue( $pageUtil->is_cart() );
	}

	/**
	 * Test is_cart()
	 */
	public function test_to_checkout() {

		// create Page Util
		$pageUtil = Services::get()->service( 'page' );

		// create cart page
		$checkout_id = $this->factory()->post->create(
			array( 'post_title' => 'Checkout', 'post_content' => '[dlm_checkout]', 'post_type' => 'page' )
		);

		// set cart ID
		update_option( 'dlm_page_checkout', $checkout_id );

		// redirect to cart via Util\Redirect
		$pageUtil->to_checkout();

		// we are on cart page now
		$this->assertTrue( $pageUtil->is_checkout() );
	}

	/**
	 * Test get_pages()
	 */
	public function test_get_pages() {

		// create Page Util
		$pageUtil = new Util\Page();

		// create other page
		$this->factory()->post->create(
			array( 'post_title' => 'Checkout', 'post_content' => '[dlm_checkout]', 'post_type' => 'page' )

		);

		// create cart page
		$cart_id = $this->factory()->post->create(
			array( 'post_title' => 'Cart', 'post_content' => '[dlm_cart]', 'post_type' => 'page' )

		);

		// get pages
		$pages = $pageUtil->get_pages();

		// check if expected total pages is correct
		$this->assertCount( 3, $pages );

		// check if page with title "checkout" exists
		$this->assertTrue( in_array( "Checkout", $pages ) );

		// check if page with ID of cart exits
		$this->assertTrue( key_exists( $cart_id, $pages ) );
	}

}
