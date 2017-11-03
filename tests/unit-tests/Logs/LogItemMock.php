<?php

class DLM_Test_Log_Item_Mock extends DLM_Log_Item {

	/**
	 * DLM_Tests_Log_Item_Mock constructor.
	 *
	 * Setup mock data
	 */
	public function __construct() {
		$this->set_user_id( 1 );
		$this->set_user_ip( "127.0.0.1" );
		$this->set_user_agent( "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36" );
		$this->set_download_id( 1 );
		$this->set_version_id( 1 );
		$this->set_version( "1.0" );
		$this->set_download_date( new DateTime( current_time( "mysql" ) ) );
		$this->set_download_status( "completed" );
	}
}