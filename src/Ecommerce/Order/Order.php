<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

class Order {

	/** @var string */
	private $status;

	/** @var \DateTimeImmutable */
	private $date_created;

	/** @var \DateTimeImmutable */
	private $date_modified;

	/** @var string */
	private $currency;

	/** @var OrderCustomer */
	private $customer;

	/** @var OrderCoupon[] */
	private $coupons;

	/** @var OrderItem[] */
	private $items;

	/** @var OrderTransaction[] */
	private $transactions;

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * @param \DateTimeImmutable $date_created
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * @param \DateTimeImmutable $date_modified
	 */
	public function set_date_modified( $date_modified ) {
		$this->date_modified = $date_modified;
	}

	/**
	 * @return string
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * @param string $currency
	 */
	public function set_currency( $currency ) {
		$this->currency = $currency;
	}

	/**
	 * @return OrderCustomer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * @param OrderCustomer $customer
	 */
	public function set_customer( $customer ) {
		$this->customer = $customer;
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
	 * @return OrderTransaction[]
	 */
	public function get_transactions() {
		return $this->transactions;
	}

	/**
	 * @param OrderTransaction[] $transactions
	 */
	public function set_transactions( $transactions ) {
		$this->transactions = $transactions;
	}
}