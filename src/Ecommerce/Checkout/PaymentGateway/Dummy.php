<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

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

		$error_message = '';

		try {

			/** @var \Never5\DownloadMonitor\Ecommerce\Order\Repository $order_repo */
			$order_repo = Services::get()->service( 'order_repository' );

			/** @var \Never5\DownloadMonitor\Ecommerce\Order\Order $order */
			$order = $order_repo->retrieve_single( $order_id );

			$order->set_status( Services::get()->service( 'order_status_factory' )->make( 'completed' ) );

			$order_repo->persist( $order );

			return new Result( true, $this->get_success_url( $order_id ) );

		} catch ( \Exception $exception ) {
			$error_message = $exception->getMessage();
		}

		return new Result( false, '', $error_message );

	}


}