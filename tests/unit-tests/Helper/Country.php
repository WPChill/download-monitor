<?php

namespace WPChill\DownloadMonitor\Tests\Helper;

use WPChill\DownloadMonitor\Shop\Services\Services;

class Country extends \DLM_Unit_Test_Case {

	/**
	 * Test get_countries()
	 */
	public function test_get_countries() {
		$countries = Services::get()->service( 'country' )->get_countries();

		$this->assertTrue( ( count( $countries ) > 0 ) );

		$this->assertTrue( in_array( "Netherlands", $countries ) );
	}

	/**
	 * Test get_country_label_by_code()
	 */
	public function test_get_country_label_by_code() {
		$country = Services::get()->service( 'country' )->get_country_label_by_code( "NL" );

		$this->assertEquals( "Netherlands", $country );
	}

}