<?php

namespace Never5\DownloadMonitor\Ecommerce\DownloadProduct;

class DownloadProduct extends \DLM_Download {

	/**
	 * @var int Price of DownloadProduct in cents
	 */
	private $price;

	/** @var bool */
	private $taxable;

	/** @var string */
	private $tax_class;

	/**
	 * @return int
	 */
	public function get_price() {
		return $this->price;
	}

	/**
	 * @param int $price
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * @return bool
	 */
	public function is_taxable() {
		return $this->taxable;
	}

	/**
	 * @param bool $taxable
	 */
	public function set_taxable( $taxable ) {
		$this->taxable = $taxable;
	}

	/**
	 * @return string
	 */
	public function get_tax_class() {
		return $this->tax_class;
	}

	/**
	 * @param string $tax_class
	 */
	public function set_tax_class( $tax_class ) {
		$this->tax_class = $tax_class;
	}

}