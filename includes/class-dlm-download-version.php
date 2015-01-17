<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Download_Version class.
 */
class DLM_Download_Version {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct( $version_id, $download_id ) {
		$this->id          = absint( $version_id );
		$this->download_id = absint( $download_id );

		// Get Version Data
		$this->version        = strtolower( get_post_meta( $this->id, '_version', true ) );
		$this->download_count = get_post_meta( $this->id, '_download_count', true );
		$this->filesize       = get_post_meta( $this->id, '_filesize', true );
		$this->md5            = get_post_meta( $this->id, '_md5', true );
		$this->sha1           = get_post_meta( $this->id, '_sha1', true );
		$this->crc32          = get_post_meta( $this->id, '_crc32', true );
		$this->mirrors        = get_post_meta( $this->id, '_files', true );

		// Get URLS
		if ( is_string( $this->mirrors ) ) {
			$this->mirrors = array_filter( (array) json_decode( $this->mirrors ) );
		} elseif ( is_array( $this->mirrors ) ) {
			$this->mirrors = array_filter( $this->mirrors );
		} else {
			$this->mirrors = array();
		}

		$this->url      = current( $this->mirrors );
		$this->filename = current( explode( '?', basename( $this->url ) ) );
		$this->filetype = strtolower( substr( strrchr( $this->filename, "." ), 1 ) );

		// If we don't have a filesize, lets get it now
		if ( $this->filesize === "" ) {
			$this->filesize = $this->get_filesize( $this->url );
		}
	}

	/**
	 * increase_download_count function.
	 *
	 * @access public
	 * @return void
	 */
	public function increase_download_count() {
		// File download_count
		$this->download_count = absint( get_post_meta( $this->id, '_download_count', true ) ) + 1;
		update_post_meta( $this->id, '_download_count', $this->download_count );

		// Parent download download_count
		$parent_download_count = absint( get_post_meta( $this->download_id, '_download_count', true ) ) + 1;
		update_post_meta( $this->download_id, '_download_count', $parent_download_count );
	}

	/**
	 * get_filesize function.
	 *
	 * @access public
	 *
	 * @param string $file_path
	 *
	 * @return string
	 */
	public function get_filesize( $file_path ) {
		// File Manager
		$file_manager = new DLM_File_Manager();

		// Get the file size
		$filesize = $file_manager->get_file_size( $file_path );

		update_post_meta( $this->id, '_filesize', $filesize );

		return $filesize;
	}

	/**
	 * get_file_hashes function.
	 *
	 * @access public
	 *
	 * @param string $file_path
	 *
	 * @return array
	 */
	public function get_file_hashes( $file_path ) {

		// File Manager
		$file_manager = new DLM_File_Manager();

		// Get the hashes
		$hashes = $file_manager->get_file_hashes( $file_path );

		update_post_meta( $this->id, '_md5', $hashes['md5'] );
		update_post_meta( $this->id, '_sha1', $hashes['sha1'] );
		update_post_meta( $this->id, '_crc32', $hashes['crc32'] );

		return $hashes;
	}
}