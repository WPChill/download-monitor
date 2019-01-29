<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\PayPal;

use Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;
use Never5\DownloadMonitor\Ecommerce\Services\Services;
use Never5\DownloadMonitor\Ecommerce\Dependencies\PayPal;

class PayPalGateway extends PaymentGateway\PaymentGateway {

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
	 * Setup paypal extension
	 */
	public function setup_gateway() {

		// run execute payment listener
		$execute_payment_listener = new ExecutePaymentListener( $this );
		$execute_payment_listener->run();

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
	 * @param \Never5\DownloadMonitor\Ecommerce\Order\Order $order
	 *
	 * @return PaymentGateway\Result
	 */
	public function process( $order ) {

		$payer = $this->get_payer( $order );

		$transaction = $this->get_transaction( $order );

		$redirectUrls = new PayPal\Api\RedirectUrls();
		$redirectUrls->setReturnUrl( $this->get_execute_payment_url( $order->get_id() ) )
		             ->setCancelUrl( $this->get_cancel_url( $order->get_id() ) );

		$payment = new PayPal\Api\Payment();
		$payment->setIntent( 'sale' )
		        ->setPayer( $payer )
		        ->setTransactions( array( $transaction ) )
		        ->setRedirectUrls( $redirectUrls );

		try {
			$payment->create( Helper::get_api_context() );
		} catch ( \Exception $ex ) {
			// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
			//ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
			return new PaymentGateway\Result( false, '', 'PayPal error: could not create payment. Please check your PayPal logs.' );
		}

		// get the URL where user can pay
		$approvalUrl = $payment->getApprovalLink();

		/**
		 * @todo insert the transaction in local database before redirecting
		 */

		return new PaymentGateway\Result( true, $approvalUrl );
	}

	/**
	 * Get the URL for executing a payment
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	private function get_execute_payment_url( $order_id ) {
		return add_query_arg( array(
			'order_id'      => $order_id,
			'paypal_action' => 'execute_payment'
		), Services::get()->service( 'page' )->get_checkout_url( 'complete' ) );
	}



	/**
	 * @param \Never5\DownloadMonitor\Ecommerce\Order\Order $order
	 *
	 * @return PayPal\Api\Payer
	 */
	private function get_payer( $order ) {
		$oc = $order->get_customer();

		// create address
		$address = new PayPal\Api\Address();

		if ( '' != $oc->get_address_1() ) {
			$address->setLine1( $oc->get_address_1() );
		}


		if ( '' != $oc->get_address_2() ) {
			$address->setLine2( $oc->get_address_2() );
		}

		if ( '' != $oc->get_postcode() ) {
			$address->setPostalCode( $oc->get_postcode() );
		}


		if ( '' != $oc->get_city() ) {
			$address->setCity( $oc->get_city() );
		}


		if ( '' != $oc->get_state() ) {
			$address->setState( $oc->get_state() );
		}

		if ( '' != $oc->get_country() ) {
			$address->setCountryCode( $oc->get_country() );
		}

		if ( '' != $oc->get_phone() ) {
			$address->setPhone( $oc->get_phone() );
		}
		$payer_info = new PayPal\Api\PayerInfo();
		$payer_info->setEmail( $oc->get_email() );
		$payer_info->setFirstName( $oc->get_first_name() );
		$payer_info->setLastName( $oc->get_last_name() );
		$payer_info->setBillingAddress( $address );

		$payer = new PayPal\Api\Payer();
		$payer->setPaymentMethod( 'paypal' );
		$payer->setPayerInfo( $payer_info );

		return $payer;
	}

	/**
	 * @param \Never5\DownloadMonitor\Ecommerce\Order\Order $order
	 *
	 * @return PayPal\Api\Transaction
	 */
	private function get_transaction( $order ) {

		$currency = Services::get()->service( 'currency' )->get_shop_currency();

		// generate items
		$items = array();
		foreach ( $order->get_items() as $order_item ) {
			$item = new PayPal\Api\Item();
			$item->setName( $order_item->get_label() )
			     ->setCurrency( $currency )
			     ->setQuantity( $order_item->get_qty() )
			     ->setSku( $order_item->get_download_id() )
			     ->setPrice( $this->cents_to_full( $order_item->get_subtotal() ) );
			$items[] = $item;
		}

		// set items in list
		$itemList = new PayPal\Api\ItemList();
		$itemList->setItems( $items );


		// set order details
		$details = new PayPal\Api\Details();
		$details->setTax( 0 )/** @todo add tax support later */
		        ->setSubtotal( $this->cents_to_full( $order->get_subtotal() ) );

		// set amount
		$amount = new PayPal\Api\Amount();
		$amount->setCurrency( $currency )
		       ->setTotal( $this->cents_to_full( $order->get_total() ) )
		       ->setDetails( $details );

		// setup transactions
		$transaction = new PayPal\Api\Transaction();
		$transaction->setAmount( $amount )
		            ->setItemList( $itemList )
		            ->setDescription( sprintf( "%s - Order #%d ", get_bloginfo( 'name' ), $order->get_id() ) )
		            ->setInvoiceNumber( $order->get_id() );

		return $transaction;
	}


	/**
	 * @param float $fl_cents
	 *
	 * @return int
	 */
	private function cents_to_full( $fl_cents ) {
		return number_format( ( $fl_cents / 100 ), 2 );
	}
}