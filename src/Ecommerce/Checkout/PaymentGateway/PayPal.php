<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

class PayPal extends PaymentGateway {

	/** @var bool */
	private $sandbox = false;

	/**
	 * PayPal constructor.
	 */
	public function __construct() {

		$this->set_id( 'paypal' );
		$this->set_title( 'Paypal' );
		$this->set_description( __( 'Pay with Paypal', 'download-monitor' ) );

		parent::__construct();

		$this->set_sandbox( '1' == $this->get_option( 'sandbox' ) );

	}

	/**
	 * @return bool
	 */
	public function is_sandbox() {
		return $this->sandbox;
	}

	/**
	 * @param bool $sandbox
	 */
	public function set_sandbox( $sandbox ) {
		$this->sandbox = $sandbox;
	}

	/**
	 * Setup gateway settings
	 */
	protected function setup_settings() {
		$this->set_settings( array(
			'enabled' => array(
				'type'        => 'checkbox',
				'title'       => 'Enabled',
				'description' => 'Check to enable this payment gateway',
				'default'     => false
			),
			'sandbox' => array(
				'type'        => 'checkbox',
				'title'       => 'Sandbox',
				'description' => 'Check to enable PayPal sandbox mode.',
				'default'     => false
			)
		) );
	}


	/**
	 * @param $order_id
	 *
	 * @return Result
	 */
	public function process( $order_id ) {
		// @todo write method
		return new Result( true, '#' );
	}

}