<?php

class DLM_Test_Download_Mock extends DLM_Download {

	/**
	 * DLM_Test_Download_Mock constructor.
	 *
	 * Setup mock data
	 */
	public function __construct() {
		$this->set_title( "Test Download" );
		$this->set_slug( "test-download" );
		$this->set_status( "publish" );
		$this->set_author( 1 );
		$this->set_description( "Test Download Description" );
		$this->set_excerpt( "Test Download Excerpt" );
	}

}