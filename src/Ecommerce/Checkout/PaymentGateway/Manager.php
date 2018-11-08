<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

class Manager {

	/** @var PaymentGateway[] */
	private $gateways = array();

	/**
	 * Manager constructor.
	 */
	public function __construct() {

		// add gateways
		$this->gateways = apply_filters( 'dlm_ecommerce_payment_gateways', array( new PayPal(), new Dummy() ) );

	}

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
					$eg[ $gateway->get_id() ] = $gateway;
				}
			}
		}

		return $eg;
	}

}