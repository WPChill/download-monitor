<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal;

use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\CaptureOrder;
use WPChill\DownloadMonitor\Shop\Services\Services;
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
		$order_id   = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order_hash = isset( $_GET['order_hash'] ) ? sanitize_text_field( wp_unslash($_GET['order_hash']) ) : '';

		if ( empty( $order_id ) || empty( $order_hash ) ) {
			$this->execute_failed( $order_id, $order_hash );
		}

		/** @var \WPChill\DownloadMonitor\Shop\Order\Repository $order_repo */
		$order_repo = Services::get()->service( 'order_repository' );
		try {
			$order = $order_repo->retrieve_single( $order_id );
		} catch ( \Exception $exception ) {
			/**
			 * @todo log error in PayPal error log ($exception->getMessage())
			 */
			$this->execute_failed( $order_id, $order_hash );

			return;
		}

		/**
		 * Get payment identifier
		 */
		$token = '';
		if ( isset( $_GET['token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		}

		/**
		 * Execute the payement
		 */
		try {

			$capture = new CaptureOrder();
			$capture->set_client( $this->gateway->get_api_context() )
					->set_order_id( $token );

			$response = $capture->captureOrder();

			// if payment is not approved, exit;
			if ( $response->getStatus() !== "COMPLETED" ) {
				throw new Exception( sprintf( "Execute payment state is %s", $response->getStatus() ) );
			}

			/**
			 * Update transaction in local database
			 */

			// update the order status to 'completed'
			$transactions = $order->get_transactions();
			foreach ( $transactions as $transaction ) {
				if ( $transaction->get_processor_transaction_id() == $response->getId() ) {
					$transaction->set_status( Services::get()->service( 'order_transaction_factory' )->make_status( 'success' ) );
					$transaction->set_processor_status( $response->getStatus() );

					try {
						$transaction->set_date_modified( new \DateTimeImmutable( current_time( 'mysql' ) ) );
					} catch ( \Exception $e ) {
						// ?
					}

					$order->set_transactions( $transactions );
					break;
				}

			}

			// set order as completed, this also persists the order
			$order->set_completed();

			/**
			 * Redirect user to "clean" complete URL
			 */
			wp_redirect( $this->gateway->get_success_url( $order->get_id(), $order->get_hash() ), 302 );
			exit;

		} catch ( \Exception $ex ) {
			/**
			 * @todo add error logging for separate PayPal log
			 */
			$this->execute_failed( $order->get_id(), $order->get_hash() );

			return;
		}

	}

	/**
	 * This method gets called when execute failed. Reason for fail will be logged in PayPal log (if enabled).
	 * User will be redirected to the checkout 'failed' endpoint.
	 *
	 * @param int $order_id
	 * @param string $order_hash
	 */
	private function execute_failed( $order_id, $order_hash ) {
		wp_redirect( $this->gateway->get_failed_url( $order_id, $order_hash ), 302 );
		exit;
	}

}