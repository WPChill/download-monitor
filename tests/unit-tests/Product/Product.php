<?php

namespace WPChill\DownloadMonitor\Tests\Product;

use WPChill\DownloadMonitor\Shop\Services\Services;
use \WPChill\DownloadMonitor\Shop\Product;

class DLM_Test_DownloadProduct extends \DLM_Unit_Test_Case {

	/**
	 * test if setting and getting price works
	 */
	public function test_price() {

		// create new DownloadProduct
		/** @var Product\Product $product */
		$product = Services::get()->service('product_factory')->make();

		// set price to 100 cents
		$product->set_price( 100 );

		// check if the price is stored correctly
		$this->assertEquals( 100, $product->get_price() );
	}

	/**
	 * Test setting price from user input
	 */
	public function test_set_price_from_user_input() {

		/** @var Product\Product $product */
		$product = Services::get()->service('product_factory')->make();

		$o_ds = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
		$o_ts = download_monitor()->service( 'settings' )->get_option( 'thousand_separator' );

		// set decimal and thousand sep
		update_option( 'dlm_decimal_separator', '.' );
		update_option( 'dlm_thousand_separator', ',' );

		// set test with thousand and decimal separator
		$product->set_price_from_user_input( "1,000.15" );
		$this->assertEquals( 100015, $product->get_price() );

		// set test with only thousand separator
		$product->set_price_from_user_input( "1,337" );
		$this->assertEquals( 133700, $product->get_price() );

		// set test with only decimal separator
		$product->set_price_from_user_input( "1234.56" );
		$this->assertEquals( 123456, $product->get_price() );

		// set test with no separators
		$product->set_price_from_user_input( "12" );
		$this->assertEquals( 1200, $product->get_price() );

		// reverse decimal and thousand sep
		update_option( 'dlm_decimal_separator', ',' );
		update_option( 'dlm_thousand_separator', '.' );

		// set test with thousand and decimal separator
		$product->set_price_from_user_input( "1.000,15" );
		$this->assertEquals( 100015, $product->get_price() );

		// set test with only thousand separator
		$product->set_price_from_user_input( "1.337" );
		$this->assertEquals( 133700, $product->get_price() );

		// set test with only decimal separator
		$product->set_price_from_user_input( "1234,56" );
		$this->assertEquals( 123456, $product->get_price() );

		// set test with no separators
		$product->set_price_from_user_input( "12" );
		$this->assertEquals( 1200, $product->get_price() );

		// reset separators
		update_option( 'dlm_decimal_separator', $o_ds );
		update_option( 'dlm_thousand_separator', $o_ts );
	}

	/**
	 * Test getting price for user input
	 */
	public function test_get_price_for_user_input() {

		// create product with price
		/** @var Product\Product $product */
		$product = Services::get()->service('product_factory')->make();
		$product->set_price( 123456 );

		$o_ds = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
		$o_ts = download_monitor()->service( 'settings' )->get_option( 'thousand_separator' );

		// set decimal and thousand sep
		update_option( 'dlm_decimal_separator', '.' );
		update_option( 'dlm_thousand_separator', ',' );

		$this->assertEquals( "1,234.56", $product->get_price_for_user_input() );

		// reverse decimal and thousand sep
		update_option( 'dlm_decimal_separator', ',' );
		update_option( 'dlm_thousand_separator', '.' );

		$this->assertEquals( "1.234,56", $product->get_price_for_user_input() );

		// reset separators
		update_option( 'dlm_decimal_separator', $o_ds );
		update_option( 'dlm_thousand_separator', $o_ts );
	}

	/**
	 * Test set_taxable and is_taxable
	 */
	public function test_taxable() {
		/** @var Product\Product $product */
		$product = Services::get()->service('product_factory')->make();
		$product->set_taxable( false );

		$this->assertFalse( $product->is_taxable() );

		$product->set_taxable( true );

		$this->assertTrue( $product->is_taxable() );
	}

	/**
	 * Test set_tax_class and get_tax_class
	 */
	public function test_tax_class() {

		/** @var Product\Product $product */
		$product = Services::get()->service('product_factory')->make();
		$product->set_tax_class( "test_tax_class" );

		$this->assertEquals( "test_tax_class", $product->get_tax_class() );
	}

}
