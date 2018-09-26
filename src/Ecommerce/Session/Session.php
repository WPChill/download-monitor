<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

class Session {

	/** @var string */
	private $key;

	/** @var string */
	private $hash;

	/** @var \DateTimeImmutable */
	private $expiry;

	/** @var string[] */
	private $coupons;

	/** @var Item\Item[] */
	private $items;

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
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}

	/**
	 * @param string $hash
	 */
	public function set_hash( $hash ) {
		$this->hash = $hash;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function get_expiry() {
		return $this->expiry;
	}

	/**
	 * @param \DateTimeImmutable $expiry
	 */
	public function set_expiry( $expiry ) {
		$this->expiry = $expiry;
	}

	/**
	 * @return string[]
	 */
	public function get_coupons() {
		return $this->coupons;
	}

	/**
	 * @param string[] $coupons
	 */
	public function set_coupons( $coupons ) {
		$this->coupons = $coupons;
	}

	/**
	 * @return Item[]
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * @param Item[] $items
	 */
	public function set_items( $items ) {
		$this->items = $items;
	}

	/**
	 * @param Item $item
	 */
	public function add_item( $item ) {

		if ( ! is_array( $this->items ) ) {
			$this->items = array();
		}

		$this->items[] = $item;
	}

	/**
	 * @param string $key
	 */
	public function remove_item( $key ) {

	}

}