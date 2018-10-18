<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout;

class PaymentGateway {

	/** @var bool */
	private $enabled;

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * @param bool $enabled
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = $enabled;
	}

}