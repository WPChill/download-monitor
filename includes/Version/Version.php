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

	/** @var bool If this version is latest version of Download */
	private $latest = false;

	/** @var string */
	private $date;

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
			DLM_Debug_Logger::log("DLM_Download_Version should not be created via the constructor. Use DLM_Version_Factory instead.");
			// Setup the class with DB data
			$this->setup( $version_id, $download_id );
		}
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function get_download_id() {
		return $this->download_id;
	}

	/**
	 * @param int $download_id
	 */
	public function set_download_id( $download_id ) {
		$this->download_id = $download_id;
	}

	/**
	 * @param bool $latest
	 */
	public function set_latest($latest) {
		$this->latest = $latest;
	}

	/**
	 * @return bool
	 */
	public function is_latest() {
		return $this->latest;
	}

	/**
	 * @return string
	 */
	public function get_date() {
		return $this->date;
	}

	/**
	 * @param string $date
	 */
	public function set_date( $date ) {
		$this->date = $date;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * Helper method that returns version.
	 * If version is empty it'll return 1 as a default version number.
	 *
	 * @return string
	 */
	public function get_version_number() {
		$version = $this->get_version();
		if ( empty( $version ) ) {
			$version = 1;
		}

		return $version;
	}

	/**
	 * Helper method that returns if this version has a version label set.
	 *
	 * @return bool
	 */
	public function has_version_number() {
		return ! empty( $this->get_version() );
	}

	/**
	 * @return int
	 */
	public function get_download_count() {
		return $this->download_count;
	}

	/**
	 * @param int $download_count
	 */
	public function set_download_count( $download_count ) {
		$this->download_count = $download_count;
	}

	/**
	 * @return string
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * @param string $filename
	 */
	public function set_filename( $filename ) {
		$this->filename = $filename;
	}

	/**
	 * @return int
	 */
	public function get_filesize() {
		return $this->filesize;
	}

	/**
	 * @param int $filesize
	 */
	public function set_filesize( $filesize ) {
		$this->filesize = $filesize;
	}

	/**
	 * Get a formatted filesize
	 */
	public function get_filesize_formatted() {
		return size_format( $this->get_filesize() );
	}

	/**
	 * @return string
	 */
	public function get_md5() {
		return $this->md5;
	}

	/**
	 * @param string $md5
	 */
	public function set_md5( $md5 ) {
		$this->md5 = $md5;
	}

	/**
	 * @return string
	 */
	public function get_sha1() {
		return $this->sha1;
	}

	/**
	 * @param string $sha1
	 */
	public function set_sha1( $sha1 ) {
		$this->sha1 = $sha1;
	}

	/**
	 * @return string
	 */
	public function get_crc32() {
		return $this->crc32;
	}

	/**
	 * @param string $crc32
	 */
	public function set_crc32( $crc32 ) {
		$this->crc32 = $crc32;
	}

	/**
	 * @return string
	 */
	public function get_filetype() {
		return $this->filetype;
	}

	/**
	 * @param string $filetype
	 */
	public function set_filetype( $filetype ) {
		$this->filetype = $filetype;
	}

	/**
	 * OLD METHODS BELOW
	 */

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

			// File Manager
			$file_manager = new DLM_File_Manager();

			// Get the file size
			$this->filesize = $file_manager->get_file_size( $this->url );

			update_post_meta( $this->id, '_filesize', $this->filesize );
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
	 *
	 * Deprecated methods below.
	 *
	 */

	/**
	 * Deprecated, use $file_manager->get_file_hashes() if you want to generate hashes
	 *
	 * @deprecated 4.0
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
		return $file_manager->get_file_hashes( $file_path );
	}
}