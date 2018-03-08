<?php

class DLM_Test_Download extends DLM_Unit_Test_Case {

	/**
	 * test exists() on id
	 */
	public function test_exists_on_id() {

		// creata a mock download. Mock download has status publish
		$download = new DLM_Test_Download_Mock();

		// should not exist due lack of id
		$this->assertNotTrue( $download->exists() );

		// should exists with ID
		$download->set_id( 1 );
		$this->assertTrue( $download->exists() );
	}

	/**
	 * test exists() on status
	 */
	public function test_exists_on_status() {

		// creata a mock download. Mock download has status publish
		$download = new DLM_Test_Download_Mock();

		// set id, only if there is an id it 'exists'
		$download->set_id( 1 );

		// should exist
		$this->assertTrue( $download->exists() );

		// should not exist on draft
		$download->set_status( "draft" );
		$this->assertFalse( $download->exists() );
	}

	/**
	 * test has_version()
	 */
	public function test_has_version() {

		// mock download has no version oob
		$download = new DLM_Test_Download_Mock();
		$this->assertFalse( $download->has_version() );

		// add version and check again
		$version = new DLM_Test_Version_Mock();
		$version->set_id( 1 );
		$download->set_version( $version );
		$this->assertTrue( $download->has_version() );
	}

	/**
	 * test get_download_count()
	 */
	public function test_get_download_count() {

		$download = new DLM_Test_Download_Mock();
		$download->set_download_count( 8 );
		$this->assertEquals( 8, $download->get_download_count() );

		$version = new DLM_Test_Version_Mock();
		$version->set_latest( false );
		$version->set_download_count( 10 );
		$download->set_version( $version );
		$this->assertEquals( 10, $download->get_download_count() );

	}

	/**
	 * test version_exists()
	 */
	public function test_version_exists() {

		// repos
		$download_repo = new DLM_WordPress_Download_Repository();
		$version_repo  = new DLM_WordPress_Version_Repository();

		// perist mock download
		$download = new DLM_Test_Download_Mock();
		$download_repo->persist( $download );

		// get and remember download id
		$download_id = $download->get_id();

		// persist mock version
		$version = new DLM_Test_Version_Mock();
		$version->set_download_id( $download_id );
		$version_repo->persist( $version );

		// get and remember version id
		$version_id = $version->get_id();

		// get clean download object from db
		$download = $download_repo->retrieve_single( $download_id );

		// check if the version exists
		$this->assertTrue( $download->version_exists( $version_id ) );

		// check if it returns false on a bogus id
		$this->assertFalse( $download->version_exists( 99 ) );
	}

	/**
	 * test get_version_ids()
	 */
	public function test_get_version_ids() {

		// repos
		$download_repo = new DLM_WordPress_Download_Repository();
		$version_repo  = new DLM_WordPress_Version_Repository();

		// perist mock download
		$download = new DLM_Test_Download_Mock();
		$download_repo->persist( $download );

		// get and remember download id
		$download_id = $download->get_id();

		// persist mock version
		$version = new DLM_Test_Version_Mock();
		$version->set_download_id( $download_id );
		$version_repo->persist( $version );

		// get and remember version id
		$version_id = $version->get_id();

		// get clean download object from db
		$download = $download_repo->retrieve_single( $download_id );

		// get version ids from download
		$version_ids = $download->get_version_ids();

		// check if the array has size of 1 and check if it contains the right id
		$this->assertEquals( 1, count( $version_ids ) );
		$this->assertEquals( $version_id, $version_ids[0] );
	}

}