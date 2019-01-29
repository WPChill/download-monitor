<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\Dummy;

use Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;
use Never5\DownloadMonitor\Ecommerce\Services\Services;

class DummyGateway extends PaymentGateway\PaymentGateway {

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
	 * @param \Never5\DownloadMonitor\Ecommerce\Order\Order $order
	 *
	 * @return PaymentGateway\Result
	 */
	public function process( $order ) {

		$error_message = '';

		try {

			/** @var \Never5\DownloadMonitor\Ecommerce\Order\Repository $order_repo */
			$order_repo = Services::get()->service( 'order_repository' );


			$order->set_status( Services::get()->service( 'order_status_factory' )->make( 'completed' ) );

			$order_repo->persist( $order );

			return new PaymentGateway\Result( true, $this->get_success_url( $order->get_id() ) );

		} catch ( \Exception $exception ) {
			$error_message = $exception->getMessage();
		}

		return new PaymentGateway\Result( false, '', $error_message );

	}


}