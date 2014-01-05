<?php

/**
 * DLM_Download_Version class.
 */
class DLM_Download_Version {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $version_id, $download_id ) {
		$this->id             = absint( $version_id );
		$this->download_id    = absint( $download_id );
		
		// Get Version Data
		$this->mirrors        = array_filter( (array) get_post_meta( $this->id, '_files', true ) );
		$this->url            = current( $this->mirrors );
		$this->filename       = current( explode( '?', basename( $this->url ) ) );
		$this->filetype       = strtolower( substr( strrchr( $this->filename, "." ), 1 ) );
		$this->version        = strtolower( get_post_meta( $this->id, '_version', true ) );
		$this->download_count = get_post_meta( $this->id, '_download_count', true );
		$this->filesize       = get_post_meta( $this->id, '_filesize', true );
		$this->md5            = get_post_meta( $this->id, '_md5', true );
		$this->sha1           = get_post_meta( $this->id, '_sha1', true );
		$this->crc32          = get_post_meta( $this->id, '_crc32', true );

		// If any data is not set, set it
		if ( $this->filesize == "" )
			$this->filesize = $this->get_filesize( $this->url );

		if ( $this->md5 == "" || $this->sha1 == "" || $this->crc32 == "" ) {
			$hashes      = $this->get_file_hashes( $this->url );
			$this->md5   = $hashes['md5'];
			$this->sha1  = $hashes['sha1'];
			$this->crc32 = $hashes['crc32'];
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
	 * @param mixed $file
	 * @return string
	 */
	public function get_filesize( $file_path ) {
		global $download_monitor;

		$filesize = $download_monitor->get_filesize( $file_path );

		update_post_meta( $this->id, '_filesize', $filesize );

		return $filesize;
	}

	/**
	 * get_file_hashes function.
	 *
	 * @access public
	 * @param mixed $file
	 * @return array
	 */
	public function get_file_hashes( $file_path ) {
		global $download_monitor;

		$hashes = $download_monitor->get_file_hashes( $file_path );

		update_post_meta( $this->id, '_md5', $hashes['md5'] );
		update_post_meta( $this->id, '_sha1', $hashes['sha1'] );
		update_post_meta( $this->id, '_crc32', $hashes['crc32'] );

		return $hashes;
	}
}