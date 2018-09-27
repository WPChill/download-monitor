<?php

namespace Never5\DownloadMonitor\Ecommerce\Session\Item;

class Item {

	/** @var string */
	private $key;

	/** @var int */
	private $download_id;

	/** @var int */
	private $qty;

	/**
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function set_key( $key ) {
		$this->key = $key;
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
	public function get_qty() {
		return $this->qty;
	}

	/**
	 * @param int $qty
	 */
	public function set_qty( $qty ) {
		$this->qty = $qty;
	}

	/**
	 * We're building these manually because implementing JsonSerializable is only available from PHP5.4+
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'key'         => $this->get_key(),
			'download_id' => $this->get_download_id(),
			'qty'         => $this->get_qty()
		);
	}
}