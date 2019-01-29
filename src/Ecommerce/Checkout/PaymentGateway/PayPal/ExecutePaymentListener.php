<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\PayPal;

use Never5\DownloadMonitor\Ecommerce\Dependencies\PayPal;
use Never5\DownloadMonitor\Ecommerce\Services\Services;
use PHPUnit\Runner\Exception;

class ExecutePaymentListener {

	private $gateway;

	/**
	 * ExecutePaymentListener constructor.
	 *
	 * @param PayPalGateway $gateway
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	public function run() {
		if ( isset( $_GET['paypal_action'] ) && 'execute_payment' === $_GET['paypal_action'] ) {
			$this->executePayment();
		}
	}

	/**
	 * Execute payment based on GET parameters
	 */
	private function executePayment() {

		/**
		 * Get order
		 */

		$order_id = absint( $_GET['order_id'] );

		/** @var \Never5\DownloadMonitor\Ecommerce\Order\Repository $order_repo */
		$order_repo = Services::get()->service( 'order_repository' );
		try {
			$order = $order_repo->retrieve_single( $order_id );
		} catch ( \Exception $exception ) {
			/**
			 * @todo log error in PayPal error log ($exception->getMessage())
			 */
			$this->execute_failed( $order_id );

			return;
		}


		/**
		 * Get Payment by paymentId
		 */
		$paymentId = $_GET['paymentId'];
		$payment   = PayPal\Api\Payment::get( $paymentId, Helper::get_api_context() );

		/**
		 * Setup PaymentExecution object
		 */
		$execution = new PayPal\Api\PaymentExecution();
		$execution->setPayerId( $_GET['PayerID'] );


		/**
		 * Execute the payement
		 */
		try {

			/**
			 * Execute the payment
			 */
			$result = $payment->execute( $execution, Helper::get_api_context() );

			// if payment is not approved, exit;
			if ( $result->getState() !== "approved" ) {
				throw new Exception( sprintf( "Execute payment state is %s", $result->getState() ) );
			}

			/**
			 * Update transaction in local database
			 */

			// update the order status to 'completed'


			$order->set_status( Services::get()->service( 'order_status_factory' )->make( 'completed' ) );

			$order_repo->persist( $order );

			/**
			 * Redirect user to "clean" complete URL
			 */
			wp_redirect( $this->gateway->get_success_url( $order->get_id() ), 302 );
			exit;

		} catch ( \Exception $ex ) {
			/**
			 * @todo add error logging for separate PayPal log
			 */
			$this->execute_failed( $order_id );

			return;
		}


		// don't think another payment fetch is needed
		/**
		 * try {
		 *
		 * $payment = PayPal\Api\Payment::get( $paymentId, Helper::get_api_context() );
		 *
		 *
		 * error_log( "START OF PAYMENT::::", 0 );
		 * error_log( print_r( $payment, 1 ), 0 );
		 * error_log( "START OF PAYMENT::::", 0 );
		 *
		 * if ( $payment->getState() === "approved" ) {
		 * }
		 *
		 * } catch ( \Exception $ex ) {
		 * }
		 */

	}

	/**
	 * This method gets called when execute failed. Reason for fail will be logged in PayPal log (if enabled).
	 * User will be redirected to the checkout 'failed' endpoint.
	 *
	 * @param int $order_id
	 */
	private function execute_failed( $order_id ) {
		wp_redirect( $this->gateway->get_failed_url( $order_id ), 302 );
		exit;
	}


}