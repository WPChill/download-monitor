<?php

namespace WPChill\DownloadMonitor\Shop\Ajax;

use WPChill\DownloadMonitor\Shop\Order;
use WPChill\DownloadMonitor\Shop\Services\Services;

class PlaceOrder extends Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'place_order' );
	}

	/**
	 * Calling this method will return a negative success to the browser
	 *
	 * @param string $error_message
	 */
	private function failed( $error_message ) {
		wp_send_json( array( 'success' => false, 'error' => $error_message ) );
		exit;
	}

	/**
	 * Parse and complete raw customer post data
	 *
	 * @return array
	 */
	private function parse_customer_post_data() {
		return array(
			'first_name' => isset( $_POST['customer']['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['first_name'] ) )  : '',
			'last_name'  => isset( $_POST['customer']['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['last_name'] ) ) : '',
			'company'    => isset( $_POST['customer']['company'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['company'] ) ) : '',
			'email'      => isset( $_POST['customer']['email'] ) ? sanitize_email( wp_unslash( $_POST['customer']['email'] ) ) : '',
			'address_1'  => isset( $_POST['customer']['address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['address_1'] ) )  : '',
			'address_2'  => isset( $_POST['customer']['address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['address_2'] ) ) : '',
			'postcode'   => isset( $_POST['customer']['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['postcode'] ) ) : '',
			'city'       => isset( $_POST['customer']['city'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['city'] ) ) : '',
			'state'      => isset( $_POST['customer']['state'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['state'] ) ) : '',
			'country'    => isset( $_POST['customer']['country'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['country'] ) ) : '',
			'phone'      => isset( $_POST['customer']['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer']['phone'] ) ) : '',
			'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''
		);
	}

	/**
	 * @param bool $success
	 * @param string $redirect
	 * @param string $error
	 */
	private function response( $success, $redirect, $error ) {
		wp_send_json( array( 'success' => $success, 'redirect' => $redirect, 'error' => $error ) );
		exit;
	}

	/**
	 * AJAX callback method
	 *
	 * @return void
	 */
	public function run() {

		// check nonce
		$this->check_nonce();

		//
		$customer_post = $this->parse_customer_post_data();

		// get gateway
		$enabled_gateways = Services::get()->service( 'payment_gateway' )->get_enabled_gateways();

		/** @var \WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway $gateway */
		$gateway = ( isset( $_POST['payment_gateway'] ) && isset( $enabled_gateways[ $_POST['payment_gateway'] ] ) ? $enabled_gateways[ sanitize_text_field( wp_unslash( $_POST['payment_gateway'] ) ) ] : null );

		/**
		 * Check if all required fields are set
		 */
		$required_fields = Services::get()->service( 'checkout_field' )->get_required_fields();
		foreach ( $required_fields as $required_field ) {
			if ( ! isset( $customer_post[ $required_field ] ) || $customer_post[ $required_field ] === "" ) {
				$this->failed( __( 'Not all required fields are set', 'download-monitor' ) );
			}
		}

		// check if gateway is valid
		if ( is_null( $gateway ) ) {
			$this->failed( __( 'Invalid Payment Gateway', 'download-monitor' ) );
		}

		// check if we need to create an order or fetch one based on id and hash
		$order_id     = absint( ( isset( $_POST['order_id'] ) ) ? $_POST['order_id'] : 0 );
		$order_hash   = ( isset( $_POST['order_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['order_hash'] ) ) : '' );
		$order        = null;
		$is_new_order = true;

		if ( $order_id > 0 && ! empty( $order_hash ) ) {
			/** @var \WPChill\DownloadMonitor\Shop\Order\WordPressRepository $op */
			try {
				$op        = Services::get()->service( 'order_repository' );
				$tmp_order = $op->retrieve_single( $order_id );

				// check order hashes
				if ( $order_hash !== $tmp_order->get_hash() ) {
					throw new \Exception( 'Order hash incorrect' );
				}

				if ( $tmp_order->get_status()->get_key() !== 'pending-payment' ) {
					throw new \Exception( 'Order status not pending payment' );
				}

				$order        = $tmp_order;
				$is_new_order = false;

			} catch ( \Exception $e ) {
				$order = null;
			}
		}

		// create order if no order is set at this point
		if ( null === $order ) {
			/** @var Order\Order $order */
			$order = Services::get()->service( 'order_factory' )->make();
		}

		/**
		 * Create OrderCustomer
		 */
		$order->set_customer( new Order\OrderCustomer(
			$customer_post['first_name'],
			$customer_post['last_name'],
			$customer_post['company'],
			$customer_post['address_1'],
			$customer_post['address_2'],
			$customer_post['city'],
			$customer_post['state'],
			$customer_post['postcode'],
			$customer_post['country'],
			$customer_post['email'],
			$customer_post['phone'],
			$customer_post['ip_address']
		) );

		// build array with order items based on current cart if this is a new order
		if ( $is_new_order ) {
			$order->set_items( Services::get()->service( 'order' )->build_order_items_from_cart() );
		}

		// persist order
		try {
			Services::get()->service( 'order_repository' )->persist( $order );
		} catch ( \Exception $exception ) {
			$this->response( false, '', $exception->getMessage() );
		}

		// run gateway
		$gateway_result = $gateway->process( $order );

		// exit if gateway was not successful
		if ( ! $gateway_result->is_success() ) {
			$this->response( false, '', sprintf( __( 'Payment gateway error: %s', 'download-monitor' ), $gateway_result->get_error_message() ) );
		}

		// order is in DB, gateway did what it had to do -> clear the cart
		Services::get()->service( 'cart' )->destroy_cart();

		// we good, send response with redirect
		$this->response( true, $gateway_result->get_redirect(), '' );

		// bye
		exit;
	}

}