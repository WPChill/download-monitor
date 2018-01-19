<?php

class DLM_Test_WordPress_Log_Item_Repository extends DLM_Unit_Test_Case {

	/**
	 * tearDown
	 */
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
		$this->assertEquals( 1, $wp_repo->num_rows() );

		// another new dummy log item
		$log2 = new DLM_Test_Log_Item_Mock();
		$wp_repo->persist( $log2 );

		// should have 2 log items in DB now
		$this->assertEquals( 2, $wp_repo->num_rows() );

		// perist an exiting log item, this should NOT increase total count
		$wp_repo->persist( $log );

		// should still have 2 items in DB
		$this->assertEquals( 2, $wp_repo->num_rows() );

	}

	/**
	 * Test test_num_rows() with filters
	 */
	public function test_num_rows_filtered() {
		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		$filters = array(
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
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// log item with user id 2 & status 'completed'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 2 );
		$log->set_download_status( "completed" );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have only 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// log item with user id 1 & status 'redirected'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "redirected" );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have only 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// log item with user id 1 & status 'completed'
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$wp_repo->persist( $log );
		unset( $log );

		// should have 2 rows now
		$this->assertEquals( 2, $wp_repo->num_rows( $filters ) );

		// expand filter with date filter of this month
		$now       = new DateTime( current_time( "mysql" ) );
		$filters[] = array(
			'key'      => 'download_date',
			'value'    => $now->format( 'Y-m-01' ),
			'operator' => '>='
		);

		$filters[] = array(
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
		$this->assertEquals( 3, $wp_repo->num_rows( $filters ) );

		// add log item with user id 1, status completed and download date of last month
		$now->modify( "-1 month" );
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$log->set_download_status( "completed" );
		$log->set_download_date( $now );
		$wp_repo->persist( $log );
		unset( $log );

		// should still have 3 rows now
		$this->assertEquals( 3, $wp_repo->num_rows( $filters ) );
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
		$this->assertEquals( 0, $log->get_id() );

		// persist via WP repo
		$wp_repo->persist( $log );

		// validate that log now has id 1
		$this->assertEquals( 1, $log->get_id() );

		// clear obj and fetch from DB
		$log_id = $log->get_id();
		unset( $log );
		$log = $wp_repo->retrieve_single( $log_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $log->get_id() );
		$this->assertEquals( "1.2.3.4", $log->get_user_ip() );

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
		$this->assertEquals( 1, $log->get_id() );

		// update log in db
		$log->set_user_ip( "1.2.3.5" );
		$wp_repo->persist( $log );

		// clear obj and fetch from DB
		$log_id = $log->get_id();
		unset( $log );
		$log = $wp_repo->retrieve_single( $log_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $log->get_id() );
		$this->assertEquals( "1.2.3.5", $log->get_user_ip() );

	}

	/**
	 * Test retrieve_single()
	 */
	public function test_retrieve_single() {
		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_version( "2.5" );
		$wp_repo->persist( $log );

		// store id and clear obj
		$log_id = $log->get_id();
		unset( $log );

		// fetch log via DB
		$log = $wp_repo->retrieve_single( $log_id );

		// check type
		$this->assertInstanceOf( "DLM_Log_Item", $log );

		// check version
		$this->assertEquals( "2.5", $log->get_version() );
	}

	/**
	 * Test retrieve function
	 */
	public function test_retrieve() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$wp_repo->persist( $log );

		// retrieve rows from db
		$rows = $wp_repo->retrieve();

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );

		// create another one
		unset( $log );
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 2 );
		$wp_repo->persist( $log );

		// retrieve rows from db
		unset( $rows );
		$rows = $wp_repo->retrieve();

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );
		$this->assertEquals( 2, $rows[1]->get_user_id() );

	}

	/**
	 * Test retrieve function with filters
	 */
	public function test_retrieve_filtered() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		$filters = array(
			array( "key" => "user_id", "value" => 1 ),
			array( "key" => "download_status", "value" => "completed" ),
		);

		// dummy log item
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$wp_repo->persist( $log );

		// retrieve rows from db
		$rows = $wp_repo->retrieve( $filters );

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );

		// create another one
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 2 );
		$wp_repo->persist( $log );

		// retrieve rows from db
		$rows = $wp_repo->retrieve( $filters );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );

		// create another one
		$log = new DLM_Test_Log_Item_Mock();
		$log->set_user_id( 1 );
		$wp_repo->persist( $log );

		// retrieve rows from db
		$rows = $wp_repo->retrieve( $filters );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );
		$this->assertEquals( 1, $rows[1]->get_user_id() );
	}

	/**
	 * Test retrieve function with limit and offset
	 */
	public function test_retrieve_limit_offset() {
		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// add 2 log items
		for ( $i = 1; $i < 3; $i ++ ) {
			$log = new DLM_Test_Log_Item_Mock();
			$log->set_user_id( $i );
			$wp_repo->persist( $log );
		}

		// get rows without any offset or limits
		$rows = $wp_repo->retrieve();

		// tests #1
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );
		$this->assertEquals( 2, $rows[1]->get_user_id() );

		// get rows without any offset or limits
		$rows = $wp_repo->retrieve( array(), 1 );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_user_id() );

		// get rows without any offset or limits
		$rows = $wp_repo->retrieve( array(), 1, 1 );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 2, $rows[0]->get_user_id() );

	}

}