<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Download_Version class.
 */
class DLM_Download_Version {

	/** @var int */
	public $id;

	/** @var int */
	public $download_id;

	/** @var string */
	public $version;

	/** @var int */
	public $download_count;

	/** @var int */
	public $filesize;

	/** @var string */
	public $md5;

	/** @var string */
	public $sha1;

	/** @var string */
	public $crc32;

	/** @var array */
	public $mirrors;

	/** @var string */
	public $url;

	/** @var string */
	public $filename;

	/** @var string */
	public $filetype;

	/**
	 * __construct function.
	 *
	 * @param int $version_id
	 * @param int $download_id
	 *
	 * @access public
	 */
	public function __construct( $version_id = 0, $download_id = 0 ) {

		// Check if both version and download id are given in constructor
		if ( 0 !== $version_id && 0 !== $download_id ) {
			// Setup the class with DB data
			$this->setup( $version_id, $download_id );
		}
	}

	/**
	 * Load data from DB. Not the ideal way to do this but we can't break BC.
	 *
	 * @param int $version_id
	 * @param int $download_id
	 */
	private function setup( $version_id, $download_id ) {

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
		$this->filename = current( explode( '?', DLM_Utils::basename( $this->url ) ) );
		$this->filetype = strtolower( substr( strrchr( $this->filename, "." ), 1 ) );

		// If we don't have a filesize, lets get it now
		if ( $this->filesize === "" ) {
			$this->filesize = $this->get_filesize( $this->url );
		}

	}

	/**
	 * Get the version slug
	 *
	 * @return string
	 */
	public function get_version_slug() {
		return sanitize_title_with_dashes( $this->version );
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