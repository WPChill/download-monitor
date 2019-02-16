<?php

use \Never5\DownloadMonitor\Shop\DownloadProduct;

class DLM_Test_DownloadProduct extends DLM_Unit_Test_Case {

	/**
	 * test if setting and getting price works
	 */
	public function test_price() {

		// create new DownloadProduct
		$download_product = new DownloadProduct\DownloadProduct();

		// set price to 100 cents
		$download_product->set_price( 100 );

		// check if the price is stored correctly
		$this->assertEquals( 100, $download_product->get_price() );
	}

	/**
	 * Test setting price from user input
	 */
	public function test_set_price_from_user_input() {

		$download_product = new DownloadProduct\DownloadProduct();

		$o_ds = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
		$o_ts = download_monitor()->service( 'settings' )->get_option( 'thousand_separator' );

		// set decimal and thousand sep
		update_option( 'dlm_decimal_separator', '.' );
		update_option( 'dlm_thousand_separator', ',' );

		// set test with thousand and decimal separator
		$download_product->set_price_from_user_input( "1,000.15" );
		$this->assertEquals( 100015, $download_product->get_price() );

		// set test with only thousand separator
		$download_product->set_price_from_user_input( "1,337" );
		$this->assertEquals( 133700, $download_product->get_price() );

		// set test with only decimal separator
		$download_product->set_price_from_user_input( "1234.56" );
		$this->assertEquals( 123456, $download_product->get_price() );

		// set test with no separators
		$download_product->set_price_from_user_input( "12" );
		$this->assertEquals( 1200, $download_product->get_price() );

		// reverse decimal and thousand sep
		update_option( 'dlm_decimal_separator', ',' );
		update_option( 'dlm_thousand_separator', '.' );

		// set test with thousand and decimal separator
		$download_product->set_price_from_user_input( "1.000,15" );
		$this->assertEquals( 100015, $download_product->get_price() );

		// set test with only thousand separator
		$download_product->set_price_from_user_input( "1.337" );
		$this->assertEquals( 133700, $download_product->get_price() );

		// set test with only decimal separator
		$download_product->set_price_from_user_input( "1234,56" );
		$this->assertEquals( 123456, $download_product->get_price() );

		// set test with no separators
		$download_product->set_price_from_user_input( "12" );
		$this->assertEquals( 1200, $download_product->get_price() );

		// reset separators
		update_option( 'dlm_decimal_separator', $o_ds );
		update_option( 'dlm_thousand_separator', $o_ts );
	}

	/**
	 * Test getting price for user input
	 */
	public function test_get_price_for_user_input() {

		// create product with price
		$download_product = new DownloadProduct\DownloadProduct();
		$download_product->set_price( 123456 );

		$o_ds = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
		$o_ts = download_monitor()->service( 'settings' )->get_option( 'thousand_separator' );

		// set decimal and thousand sep
		update_option( 'dlm_decimal_separator', '.' );
		update_option( 'dlm_thousand_separator', ',' );

		$this->assertEquals( "1,234.56", $download_product->get_price_for_user_input() );

		// reverse decimal and thousand sep
		update_option( 'dlm_decimal_separator', ',' );
		update_option( 'dlm_thousand_separator', '.' );

		$this->assertEquals( "1.234,56", $download_product->get_price_for_user_input() );

		// reset separators
		update_option( 'dlm_decimal_separator', $o_ds );
		update_option( 'dlm_thousand_separator', $o_ts );
	}


}
