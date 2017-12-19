<?php

/**
 * Class DLM_Test_Version_Mock
 *
 * Only use for testing! Note that version is not attached to a download, this needs to be done in the test.
 */
class DLM_Test_Version_Mock extends DLM_Download_Version {

	/**
	 * DLM_Test_Download_Mock constructor.
	 *
	 * Setup mock data
	 */
	public function __construct() {
		$this->set_author( 1 );
		$this->set_menu_order( 1 );
		$this->set_latest( true );
		$this->set_date( new DateTime( current_time( "mysql" ) ) );
		$this->set_version( "1.0" );
		$this->set_download_count( 1 );


		// we use the WordPress license file for the mock version
		$mock_file_path = ABSPATH . '/license.txt';

		/** @var DLM_File_Manager $file_manager */
		$file_manager = download_monitor()->service( 'file_manager' );

		/** @var DLM_Hasher $hasher */
		$hasher = download_monitor()->service( 'hasher' );
		$hashes = $hasher->get_file_hashes( $mock_file_path );

		$this->set_filesize( $file_manager->get_file_size( $mock_file_path ) );
		$this->set_md5( $hashes['md5'] );
		$this->set_sha1( $hashes['sha1'] );
		$this->set_sha256( $hashes['sha256'] );
		$this->set_crc32b( $hashes['crc32b'] );

		$this->set_mirrors( array( $mock_file_path ) );
		$this->set_url( $mock_file_path );

		// filename
		$filename = $file_manager->get_file_name( $mock_file_path );
		$this->set_filename( $filename );

		// filetype
		$this->set_filetype( $file_manager->get_file_type( $filename ) );

	}

}