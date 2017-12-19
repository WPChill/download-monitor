<?php

class DLM_Test_WordPress_Download_Repository extends DLM_Unit_Test_Case {

	/**
	 * tearDown
	 */
	public function tearDown() {
		global $wpdb;
		parent::tearDown();
		DLM_Test_WP_DB_Helper::truncate( $wpdb->posts );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->postmeta );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->term_relationships );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->term_taxonomy );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->termmeta );
		DLM_Test_WP_DB_Helper::truncate( $wpdb->terms );
	}

	/**
	 * Test num_rows() without filters
	 */
	public function test_num_rows() {

		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// dummy download item
		$download = new DLM_Test_Download_Mock();
		$wp_repo->persist( $download );

		// should have 1 log item in DB now
		$this->assertEquals( 1, $wp_repo->num_rows() );

		// dummy download item
		$download = new DLM_Test_Download_Mock();
		$wp_repo->persist( $download );

		// should have 2 log items in DB now
		$this->assertEquals( 2, $wp_repo->num_rows() );

		// perist an exiting log item, this should NOT increase total count
		$wp_repo->persist( $download );

		// should still have 2 items in DB
		$this->assertEquals( 2, $wp_repo->num_rows() );

	}

	/**
	 * Test test_num_rows() with author filter
	 */
	public function test_num_rows_filtered_author() {
		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// filter
		$filters = array( 'author' => 1 );

		// dummy download item
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 1 );
		$wp_repo->persist( $download );

		// should have 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy download item with author 2
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 2 );
		$wp_repo->persist( $download );

		// should still have only 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy download item with author 1
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 1 );
		$wp_repo->persist( $download );

		// should now have 2 results
		$this->assertEquals( 2, $wp_repo->num_rows( $filters ) );
	}

	/**
	 * Test test_num_rows() with category filter
	 */
	public function test_num_rows_filtered_category() {
		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// filter
		$filters = array(
			'tax_query' => array(
				array(
					'taxonomy' => 'dlm_download_category',
					'field'    => 'slug',
					'terms'    => 'testcat'
				)
			)
		);

		// dummy term
		$term = wp_insert_term( 'testcat', 'dlm_download_category', array( 'slug' => 'testcat' ) );
		if ( is_wp_error( $term ) ) {
			throw new Exception( "Failed inserting term" );
		}
		$term_id = $term['term_id'];

		// dummy download item which will get dummy term
		$download = new DLM_Test_Download_Mock();
		$wp_repo->persist( $download );
		wp_set_post_terms( $download->get_id(), array( $term_id ), 'dlm_download_category', false );

		// should have 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy download item with no terms
		$download = new DLM_Test_Download_Mock();
		$wp_repo->persist( $download );

		// should still have only 1 result
		$this->assertEquals( 1, $wp_repo->num_rows( $filters ) );

		// dummy download item which will get dummy term
		$download = new DLM_Test_Download_Mock();
		$wp_repo->persist( $download );
		wp_set_post_terms( $download->get_id(), array( $term_id ), 'dlm_download_category', false );

		// should now have 2 results
		$this->assertEquals( 2, $wp_repo->num_rows( $filters ) );
	}

	/**
	 * Test retrieve_single()
	 */
	public function test_retrieve_single() {
		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// dummy download
		$download = new DLM_Test_Download_Mock();
		$download->set_download_count( 8 );
		$wp_repo->persist( $download );

		// get newly inserted download ID
		$download_id = $download->get_id();

		// fetch log via DB
		$download = $wp_repo->retrieve_single( $download_id );

		// check type
		$this->assertInstanceOf( "DLM_Download", $download );

		// check version
		$this->assertEquals( 8, $download->get_download_count() );
	}

	/**
	 * Test retrieve function
	 */
	public function test_retrieve() {

		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// dummy log item
		$download = new DLM_Test_Download_Mock();
		$download->set_title( "Title 1" );
		$wp_repo->persist( $download );

		// retrieve rows from db
		$rows = $wp_repo->retrieve();

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( "Title 1", $rows[0]->get_title() );

		// create another one
		$download = new DLM_Test_Download_Mock();
		$download->set_title( "Title 2" );
		$wp_repo->persist( $download );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve( array( 'orderby' => 'ID', 'order' => 'ASC' ) );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( "Title 1", $rows[0]->get_title() );
		$this->assertEquals( "Title 2", $rows[1]->get_title() );

	}

	/**
	 * Test retrieve function
	 */
	public function test_retrieve_filtered() {

		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// filter
		$filters = array( 'author' => 1, 'orderby' => 'ID', 'order' => 'ASC' );

		// dummy log item
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 1 );
		$wp_repo->persist( $download );

		// retrieve rows from db
		$rows = $wp_repo->retrieve( $filters );

		// tests
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );

		// create another one
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 2 );
		$wp_repo->persist( $download );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve( $filters );

		// tests #2
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 1, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );

		// create another one
		$download = new DLM_Test_Download_Mock();
		$download->set_author( 1 );
		$wp_repo->persist( $download );

		// retrieve rows from db, set an order(by) filter so we fetch ordered on inserted order
		$rows = $wp_repo->retrieve( $filters );

		// tests #3
		$this->assertInternalType( "array", $rows );
		$this->assertCount( 2, $rows );
		$this->assertEquals( 1, $rows[0]->get_author() );
		$this->assertEquals( 1, $rows[1]->get_author() );

	}


	/**
	 * Test persist() on new download
	 */
	public function test_persist_new() {

		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// dummy download item
		$download = new DLM_Test_Download_Mock();
		$download->set_download_count( 8 );

		// validate that currently it has no id
		$this->assertEquals( 0, $download->get_id() );

		// persist via WP repo
		$wp_repo->persist( $download );

		// validate that log now has id 1
		$this->assertEquals( 1, $download->get_id() );

		// clear obj and fetch from DB
		$download_id = $download->get_id();
		$download    = $wp_repo->retrieve_single( $download_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $download->get_id() );
		$this->assertEquals( 8, $download->get_download_count() );
	}

	/**
	 * Test persist()
	 */
	public function test_persist_existing() {

		// repo
		$wp_repo = new DLM_WordPress_Download_Repository();

		// dummy download item
		$download = new DLM_Test_Download_Mock();
		$download->set_download_count( 8 );
		$wp_repo->persist( $download );

		// validate that log now has id 1
		$this->assertEquals( 1, $download->get_id() );

		// update log in db
		$download->set_download_count( 10 );
		$wp_repo->persist( $download );

		// clear obj and fetch from DB
		$download_id = $download->get_id();
		$download = $wp_repo->retrieve_single( $download_id );

		// validate that we were able to retrieve to just saved log
		$this->assertEquals( 1, $download->get_id() );
		$this->assertEquals( 10, $download->get_download_count() );

	}
}