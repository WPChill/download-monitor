<?php

namespace WPChill\DownloadMonitor\Tests\Helper;

use WPChill\DownloadMonitor\Shop\Services\Services;

class Currency extends \DLM_Unit_Test_Case {

	/**
	 * Test get_available_currencies()
	 */
	public function test_get_available_currencies() {
		$currencies = Services::get()->service( 'currency' )->get_available_currencies();

		$this->assertTrue( ( count( $currencies ) > 0 ) );

		$this->assertTrue( in_array( "Euros", $currencies ) );
	}

	/**
	 * Test get_shop_currency()
	 */
	public function test_get_shop_currency() {
		update_option( "dlm_currency", "EUR" );
		$this->assertEquals( "EUR", Services::get()->service( 'currency' )->get_shop_currency() );

		update_option( "dlm_currency", "USD" );
		$this->assertEquals( "USD", Services::get()->service( 'currency' )->get_shop_currency() );
	}

	/**
	 * Test get_currency_symbol
	 */
	public function test_get_currency_symbol_empty() {
		update_option( "dlm_currency", "EUR" );
		$this->assertEquals( "&euro;", Services::get()->service( 'currency' )->get_currency_symbol() );
		update_option( "dlm_currency", "USD" );
	}

	/**
	 * Test get_currency_symbol
	 */
	public function test_get_currency_symbol() {
		$this->assertEquals( "&yen;", Services::get()->service( 'currency' )->get_currency_symbol( "CNY" ) );
	}

	/**
	 * Test get_currency_position
	 */
	public function test_get_currency_position() {
		$this->assertEquals( "left", Services::get()->service( 'currency' )->get_currency_position() );
	}

}