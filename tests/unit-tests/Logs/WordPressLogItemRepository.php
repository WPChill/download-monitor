<?php

class DLM_Test_WordPress_Log_Item_Repository extends DLM_Unit_Test_Case {

	public function tearDown() {
		global $wpdb;
		parent::tearDown();
		DLM_Test_WP_DB_Helper::truncate( $wpdb->download_log );
	}

	/**
	 * Test test_num_rows() without any filters
	 */
	public function test_num_rows() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$wp_repo->persist( $log );

		// should have 1 log item in DB now
		$this->assertEquals( $wp_repo->num_rows(), 1 );

		// another new dummy log item
		$log2 = new DLM_Test_Log_Item_Mock();
		$wp_repo->persist( $log2 );

		// should have 2 log items in DB now
		$this->assertEquals( $wp_repo->num_rows(), 2 );

		// perist an exiting log item, this should NOT increase total count
		$wp_repo->persist( $log );

		// should still have 2 items in DB
		$this->assertEquals( $wp_repo->num_rows(), 2 );

	}

	/**
	 * Test test_num_rows() with filters
	 */
	public function test_num_rows_filtered() {
		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		$filter_user = array(
			array( "key" => "user_id", "value" => 1 ),
			array( "key" => "download_status", "value" => "completed" ),
		);

		// log item with user id 1 & status 'completed'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$wp_repo->persist( $log );
		unset( $log );

		// should have 1 result
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 1 );

		// log item with user id 2 & status 'completed'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 2 );
		$log->set_download_status( "completed" );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have only 1 result
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 1 );

		// log item with user id 1 & status 'redirected'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "redirected" );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have only 1 result
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 1 );

		// log item with user id 1 & status 'completed'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$wp_repo->persist( $log );
		unset( $log );

		// should have 2 rows now
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 2 );

		// expand filter with date filter of this month
		$now           = new DateTime( current_time( "mysql" ) );
		$filter_user[] = array(
			'key'      => 'download_date',
			'value'    => $now->format( 'Y-m-01' ),
			'operator' => '>='
		);

		$filter_user[] = array(
			'key'      => 'download_date',
			'value'    => $now->format( 'Y-m-t' ),
			'operator' => '<='
		);

		// add log item with user id 1, status completed and download date of now
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$log->set_download_date( $now );
		$wp_repo->persist( $log );
		unset( $log );

		// should have 3 rows now
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 3 );

		// add log item with user id 1, status completed and download date of last month
		$now->modify( "-1 month" );
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$log->set_download_date( $now );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have 3 rows now
		$this->assertEquals( $wp_repo->num_rows( $filter_user ), 3 );
	}

	/**
	 * Test persist() on new log item
	 */
	public function test_persist_new() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_ip( "1.2.3.4" );

		// validate that currently it has no id
		$this->assertEquals( $log->get_id(), 0 );

		// persist via WP repo
		$wp_repo->persist( $log );

		// validate that log now has id 1
		$this->assertEquals( $log->get_id(), 1 );

		// clear obj and fetch from DB
		$log_id = $log->get_id();
		unset( $log );
		$log = $wp_repo->retrieve_single( $log_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( $log->get_id(), 1 );
		$this->assertEquals( $log->get_user_ip(), "1.2.3.4" );

	}

	/**
	 * Test persist()
	 */
	public function test_persist_existing() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_ip( "1.2.3.4" );
		$wp_repo->persist( $log );

		// validate that log now has id 1
		$this->assertEquals( $log->get_id(), 1 );

		// update log in db
		$log->set_user_ip( "1.2.3.5" );
		$wp_repo->persist( $log );

		// clear obj and fetch from DB
		$log_id = $log->get_id();
		unset( $log );
		$log = $wp_repo->retrieve_single( $log_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( $log->get_id(), 1 );
		$this->assertEquals( $log->get_user_ip(), "1.2.3.5" );

	}

}