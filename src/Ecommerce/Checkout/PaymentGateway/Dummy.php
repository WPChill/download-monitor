<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

class Dummy extends PaymentGateway {

	/**
	 * PayPal constructor.
	 */
	public function __construct() {

		$this->set_id( 'dummy' );
		$this->set_title( 'Dummy' );
		$this->set_description( __( 'Dummy payments are not real payments, used for testing your website.', 'download-monitor' ) );

		parent::__construct();

	}

	/**
	 * Process the payment
	 *
	 * @param int $order_id
	 *
	 * @return Result
	 */
	public function process( $order_id ) {

		/**
		 *
		 */

		return new Result( true, '' );

	}


}