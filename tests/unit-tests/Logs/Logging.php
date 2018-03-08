<?php

class DLM_Test_Logging extends DLM_Unit_Test_Case {

	public function test_has_ip_downloaded_version() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// get ip address
		$ip = DLM_Utils::get_visitor_ip();

		// logging object
		$logging = new DLM_Logging();

		// create new version
		$version = new DLM_Download_Version();
		$version->set_id( 1 );

		// create log item
		$dummy_log = new DLM_Log_Item();
		$dummy_log->set_user_id( 0 );
		$dummy_log->set_user_ip( $ip );
		$dummy_log->set_version_id( 1 );
		$dummy_log->set_user_agent( 'test' );
		$dummy_log->set_version( '1.0' );

		// check on empty database
		$this->assertEquals( false, $logging->has_ip_downloaded_version( $version ) );

		// store log item
		$wp_repo->persist( $dummy_log );

		// now we should have already downloaded the file
		$this->assertEquals( true, $logging->has_ip_downloaded_version( $version ) );

	}
}