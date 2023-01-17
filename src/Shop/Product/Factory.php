<?php

namespace WPChill\DownloadMonitor\Shop\Product;

class Factory {

	/**
	 *
	 * @return Product
	 */
	public function make() {

		$product = new Product();

		return $product;
	}

}