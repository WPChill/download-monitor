<?php

class DLM_Test_WordPress_Version_Repository extends DLM_Unit_Test_Case {

	/**
	 * tearDown
	 */
	public function tearDown() {
		global $wpdb;
		parent::tearDown();
		DLM_Test_WP_DB_Helper::truncate( $wpdb->posts );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->postmeta );
	}

	/**
	 * Test num_rows() without filters
	 */
	public function test_num_rows() {

		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$wp_repo->persist( $version );

		// should have 1 log item in DB now
		$this->assertEquals( 1, $wp_repo->num_rows() );

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$wp_repo->persist( $version );

		// should have 2 log items in DB now
		$this->assertEquals( 2, $wp_repo->num_rows() );

		// perist an exiting log item, this should NOT increase total count
		$wp_repo->persist( $version );

		// should still have 2 items in DB
		$this->assertEquals( 2, $wp_repo->num_rows() );
	}

	/**
	 * Test test_num_rows() with author filter
	 */
	public function test_num_rows_filtered_author() {
		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// filter
		$filters = array( 'author' => 1 );

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 1 );
		$wp_repo->persist( $version );

		// should have 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy version item with author 2
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 2 );
		$wp_repo->persist( $version );

		// should still have only 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy version item with author 1
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 1 );
		$wp_repo->persist( $version );

		// should now have 2 results
		$this->assertEquals( 2, $wp_repo->num_rows( $filters ) );
	}

	/**
	 * Test retrieve_single()
	 */
	public function test_retrieve_single() {
		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_download_count( 7 );
		$wp_repo->persist( $version );

		// get newly inserted version ID
		$version_id = $version->get_id();

		// fetch log via DB
		$version = $wp_repo->retrieve_single( $version_id );

		// check type
		$this->assertInstanceOf( "DLM_Download_Version", $version );

		// check version
		$this->assertEquals( 7, $version->get_download_count() );
	}

	/**
	 * Test retrieve function
	 */
	public function test_retrieve() {

		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_version( "1.1" );
		$version->set_menu_order( 1 );
		$wp_repo->persist( $version );

		// retrieve rows from db
		$rows = $wp_repo->retrieve();

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( "1.1", $rows[0]->get_version() );

		// create another one
		$version = new DLM_Test_Version_Mock();
		$version->set_version( "1.2" );
		$version->set_menu_order( 0 );
		$wp_repo->persist( $version );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve();

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( "1.2", $rows[0]->get_version() );
		$this->assertEquals( "1.1", $rows[1]->get_version() );

	}

	/**
	 * Test retrieve function
	 */
	public function test_retrieve_filtered() {

		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// filter
		$filters = array( 'author' => 1 );

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 1 );
		$version->set_version( "1.1" );
		$version->set_menu_order( 1 );
		$wp_repo->persist( $version );

		// retrieve rows from db
		$rows = $wp_repo->retrieve( $filters );

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );
		$this->assertEquals( "1.1", $rows[0]->get_version() );

		// version with author 2
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 2 );
		$wp_repo->persist( $version );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve( $filters );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );
		$this->assertEquals( "1.1", $rows[0]->get_version() );

		// another one
		$version = new DLM_Test_Version_Mock();
		$version->set_author( 1 );
		$version->set_version( "1.5" );
		$version->set_menu_order( 0 );
		$wp_repo->persist( $version );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve( $filters );

		// tests #3
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );
		$this->assertEquals( "1.5", $rows[0]->get_version() );
		$this->assertEquals( 1, $rows[1]->get_author() );
		$this->assertEquals( "1.1", $rows[1]->get_version() );
	}

	/**
	 * Test persist() on new download
	 */
	public function test_persist_new() {

		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_download_count( 7 );

		// validate that currently it has no id
		$this->assertEquals( 0, $version->get_id() );

		// persist
		$wp_repo->persist( $version );

		// persist via WP repo
		$wp_repo->persist( $version );

		// validate that log now has id 1
		$this->assertEquals( 1, $version->get_id() );

		// clear obj and fetch from DB
		$version_id = $version->get_id();
		$version    = $wp_repo->retrieve_single( $version_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $version->get_id() );
		$this->assertEquals( 7, $version->get_download_count() );
	}

	/**
	 * Test persist()
	 */
	public function test_persist_existing() {

		// repo
		$wp_repo = new DLM_WordPress_Version_Repository();

		// dummy version item
		$version = new DLM_Test_Version_Mock();
		$version->set_download_count( 7 );
		$wp_repo->persist( $version );

		// validate that log now has id 1
		$this->assertEquals( 1, $version->get_id() );
		$this->assertEquals( 7, $version->get_download_count() );

		// update log in db
		$version->set_download_count( 10 );
		$wp_repo->persist( $version );

		// clear obj and fetch from DB
		$version_id = $version->get_id();
		$version    = $wp_repo->retrieve_single( $version_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $version->get_id() );
		$this->assertEquals( 10, $version->get_download_count() );

	}

}