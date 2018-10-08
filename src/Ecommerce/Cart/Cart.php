<?php

namespace Never5\DownloadMonitor\Ecommerce\Cart;

class Cart {

	/** @var Item[] */
	private $items;

	/** @var Coupon[] */
	private $coupons;

	/** @var int */
	private $subtotal;

	/** @var int */
	private $total;

	/** @var int */
	private $tax_total;

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
	 * @return Coupon[]
	 */
	public function get_coupons() {
		return $this->coupons;
	}

	/**
	 * @param Coupon[] $coupons
	 */
	public function set_coupons( $coupons ) {
		$this->coupons = $coupons;
	}

	/**
	 * @return int
	 */
	public function get_subtotal() {
		return $this->subtotal;
	}

	/**
	 * @param int $subtotal
	 */
	public function set_subtotal( $subtotal ) {
		$this->subtotal = $subtotal;
	}

	/**
	 * @return int
	 */
	public function get_total() {
		return $this->total;
	}

	/**
	 * @param int $total
	 */
	public function set_total( $total ) {
		$this->total = $total;
	}

	/**
	 * @return int
	 */
	public function get_tax_total() {
		return $this->tax_total;
	}

	/**
	 * @param int $tax_total
	 */
	public function set_tax_total( $tax_total ) {
		$this->tax_total = $tax_total;
	}

}