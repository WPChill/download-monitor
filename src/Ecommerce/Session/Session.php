<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

class Session {

	/** @var string */
	private $key;

	/** @var string */
	private $hash;

	/** @var \DateTimeImmutable */
	private $expiry;

	/** @var \Never5\DownloadMonitor\Ecommerce\Order\OrderCoupon[] */
	private $coupons;

	/** @var \Never5\DownloadMonitor\Ecommerce\Order\OrderItem[] */
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
	 * @return OrderCoupon[]
	 */
	public function get_coupons() {
		return $this->coupons;
	}

	/**
	 * @param OrderCoupon[] $coupons
	 */
	public function set_coupons( $coupons ) {
		$this->coupons = $coupons;
	}

	/**
	 * @return OrderItem[]
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * @param OrderItem[] $items
	 */
	public function set_items( $items ) {
		$this->items = $items;
	}

	/**
	 * @return string
	 */
	public function to_data() {
		return '';
	}

	/**
	 * @param string $data
	 */
	public function load_from_data( $data ) {

	}
	
}