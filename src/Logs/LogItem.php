<?php

class DLM_Log_Item {

	/** @var int */
	private $id = 0;

	/** @var int */
	private $user_id;

	/** @var int */
	private $download_id;

	/** @var int */
	private $version_id;

	/** @var string */
	private $version;

	/** @var array */
	private $meta_data = array();

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
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
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
	 * @return int
	 */
	public function get_version_id() {
		return $this->version_id;
	}

	/**
	 * @param int $version_id
	 */
	public function set_version_id( $version_id ) {
		$this->version_id = $version_id;
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
	 * @return array
	 */
	public function get_meta_data() {
		return $this->meta_data;
	}

	/**
	 * @param array $meta_data
	 */
	public function set_meta_data( $meta_data ) {
		$this->meta_data = $meta_data;
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function add_meta_data_item( $key, $value ) {

		// get meta
		$meta = $this->get_meta_data();

		// just to be sure we have an array
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		// set new meta. We're not checking if it exists, this means we override by default. Check in your code if exists before adding!
		$meta[ $key ] = $value;

		// set meta
		$this->set_meta_data( $meta );
	}

	/**
	 * Checks if meta data exists for given key
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function meta_data_exist( $key ) {
		$meta = $this->get_meta_data();

		return ( is_array( $meta ) && isset( $meta[ $key ] ) );
	}

}