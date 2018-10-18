<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout;

class Manager {

	/** @var PaymentGateway[] */
	private $gateways = array();

	/**
	 * Returns all payment gateways
	 *
	 * @return PaymentGateway[]
	 */
	public function get_all_gateways() {
		return $this->gateways;
	}

	/**
	 * Returns all enabled payment gateways
	 *
	 * @return PaymentGateway[]
	 */
	public function get_enabled_gateways() {
		$eg = array();
		if ( ! empty( $this->gateways ) ) {
			/** @var PaymentGateway $gateway */
			foreach ( $this->gateways as $gateway ) {
				if ( $gateway->is_enabled() ) {
					$eg[] = $gateway;
				}
			}
		}

		return $eg;
	}

}