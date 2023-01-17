<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal;

use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway;
use WPChill\DownloadMonitor\Shop\Services\Services;
use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Api as PayPalApi;
use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Core\PayPalHttpClient;
use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Core\ProductionEnvironment;
use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Core\SandboxEnvironment;

class PayPalGateway extends PaymentGateway\PaymentGateway {

	/** @var bool */
	private $sandbox = false;

	/**
	 * PayPal constructor.
	 */
	public function __construct() {

		$this->set_id( 'paypal' );
		$this->set_title( 'PayPal' );
		$this->set_description( __( 'Pay with PayPal', 'download-monitor' ) );
		$this->set_enabled( true );
		parent::__construct();

		$this->set_sandbox( '1' == $this->get_option( 'sandbox_enabled' ) );

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
	 * Returns API context used in all PayPal API calls
	 *
	 * @return PayPal\Rest\ApiContext
	 */
	public function get_api_context() {

		$client_id     = "";
		$client_secret = "";

		if ( ! $this->is_sandbox() ) {
			// get live keys
			$client_id     = trim( $this->get_option( 'client_id' ) );
			$client_secret = trim( $this->get_option( 'client_secret' ) );

			return new PayPalHttpClient( new ProductionEnvironment( $client_id, $client_secret ) );
		} else {
			// get sandbox keys
			$client_id     = trim( $this->get_option( 'sandbox_client_id' ) );
			$client_secret = trim( $this->get_option( 'sandbox_client_secret' ) );

			return new PayPalHttpClient( new SandboxEnvironment( $client_id, $client_secret ) );
		}
	}

	/**
	 * Setup PayPal extension
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

		$application_desc = __( "In order to allow users to pay via PayPal on your website, you need to create an application in PayPal's developer portal. After you've done so, please copy the Client ID and Secret and set them here.", 'download-monitor' );
		$application_desc .= "<br/>";
		$application_desc .= "<a href='https://developer.paypal.com/developer/applications/create' target='_blank'>" . __( "Click here to create a new PayPal application", 'download-monitor' ) . "</a>";
		$application_desc .= " - ";
		$application_desc .= "<a href='https://www.download-monitor.com/kb/payment-gateway-paypal' target='_blank'>" . __( "Click here to read the full documentation page", 'download-monitor' ) . "</a>";

		$sandbox_desc = __( 'The same fields from your PayPal application but from the "sandbox" mode.', 'download-monitor' );
		$sandbox_desc .= " <a href='https://www.download-monitor.com/kb/payment-gateway-paypal' target='_blank'>" . __( "Click here to read more on how to set this up", 'download-monitor' ) . "</a>";

		$this->set_settings( array(
			array(
				'name'  => 'invoice_prefix',
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Invoice Prefix', 'download-monitor' ),
				'desc'  => __( "This prefix is added to the paypal invoice ID. If you run multiple stores with the same PayPal account, enter an unique prefix per store here.", 'download-monitor' )
			),
			array(
				'name'  => '',
				'type'  => 'title',
				'title' => __( 'Application Details', 'download-monitor' )
			),
			array(
				'name' => '',
				'type' => 'desc',
				'text' => $application_desc
			),
			array(
				'name'  => 'client_id',
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Application Client ID', 'download-monitor' ),
				'desc'  => __( 'Your application client ID.', 'download-monitor' )
			),
			array(
				'name'  => 'client_secret',
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Application Client Secret', 'download-monitor' ),
				'desc'  => __( 'Your application client secret.', 'download-monitor' )
			),
			array(
				'name'  => '',
				'type'  => 'title',
				'title' => __( 'Test Settings', 'download-monitor' )
			),
			array(
				'name' => '',
				'type' => 'desc',
				'text' => $sandbox_desc
			),
			array(
				'name'     => 'sandbox_enabled',
				'type'     => 'checkbox',
				'label'    => __( 'Sandbox', 'download-monitor' ),
				'desc'     => __( 'Check to enable PayPal sandbox mode. This allows you to test your PayPal integration.', 'download-monitor' ),
				'cb_label' => __( 'Enable Sandbox', 'download-monitor' ),
				'std'      => 0
			),
			array(
				'name'  => 'sandbox_client_id',
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Sandbox Client ID', 'download-monitor' ),
				'desc'  => __( 'Your application sandbox client ID.', 'download-monitor' )
			),
			array(
				'name'  => 'sandbox_client_secret',
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Sandbox Client Secret', 'download-monitor' ),
				'desc'  => __( 'Your application sandbox client secret.', 'download-monitor' )
			),
		) );
	}

	/**
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return PaymentGateway\Result
	 */
	public function process( $order ) {

		// Payer
		$payer = $this->get_payer( $order );

		// Transaction
		$transaction = $this->get_transaction( $order );

		// Redirect URLs
		$redirectUrls = new PayPalApi\RedirectUrls();
		$redirectUrls->setReturnUrl( $this->get_execute_payment_url( $order ) )
		             ->setCancelUrl( $this->get_cancel_url( $order->get_id(), $order->get_hash() ) );

		// Payment
		$payment = new CreateOrder();
		$payment->setClient( $this->get_api_context() );
		$payment->setIntent( 'CAPTURE' )
				->setPayer( $payer )
		        ->setTransactions( array( $transaction ) )
		        ->setRedirectUrls( $redirectUrls );

		try {
			$payment->createOrder();
		} catch ( \Exception $ex ) {
			return new PaymentGateway\Result( false, '', __( 'Could not create payment. Please check your PayPal logs.', 'download-monitor' ) );
		}

		// create local transaction
		/** @var \WPChill\DownloadMonitor\Shop\Order\Transaction\OrderTransaction $dlm_transaction */
		$dlm_transaction = Services::get()->service( 'order_transaction_factory' )->make();
		$dlm_transaction->set_amount( $order->get_total() );
		$dlm_transaction->set_processor( $this->get_id() );
		$dlm_transaction->set_processor_nice_name( $this->get_title() );
		$dlm_transaction->set_processor_transaction_id( $payment->getId() );
		$dlm_transaction->set_processor_status( $payment->getStatus() );

		// add transaction to order
		$order->add_transaction( $dlm_transaction );

		// persist order
		try {
			Services::get()->service( 'order_repository' )->persist( $order );
		} catch ( \Exception $exception ) {
			return new PaymentGateway\Result( false, '', __( 'Error saving order with PayPal transaction.', 'download-monitor' ) );
		}

		// get the URL where user can pay
		$approvalUrl = $payment->getApprovalLink();

		return new PaymentGateway\Result( true, $approvalUrl );
	}

	/**
	 * Get the URL for executing a payment
	 *
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return string
	 */
	private function get_execute_payment_url( $order ) {
		return add_query_arg( array(
			'order_id'      => $order->get_id(),
			'order_hash'    => $order->get_hash(),
			'paypal_action' => 'execute_payment'
		), Services::get()->service( 'page' )->get_checkout_url( 'complete' ) );
	}

	/**
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return PayPal\Api\Payer
	 */
	private function get_payer( $order ) {
		$oc = $order->get_customer();

		// create address
		$address = new PayPalApi\Address();

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

		$payer_info = new PayPalApi\PayerInfo();
		$payer_info->setEmailAddress( $oc->get_email() );
		$payer_info->setName( $oc->get_first_name(), $oc->get_last_name() );
		$payer_info->setBillingAddress( $address );

		$payer = new PayPalApi\Payer();
		$payer->setPayerInfo( $payer_info );

		return $payer;
	}

	/**
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return PayPal\Api\Transaction
	 */
	private function get_transaction( $order ) {

		$currency = Services::get()->service( 'currency' )->get_shop_currency();

		// generate items
		$items = array();
		foreach ( $order->get_items() as $order_item ) {
			$item = new PayPalApi\Item();
			$item->setName( $order_item->get_label() )
			     ->setCurrency( $currency )
			     ->setQuantity( $order_item->get_qty() )
			     ->setSku( $order_item->get_product_id() )
			     ->setPrice( $this->cents_to_full( $order_item->get_subtotal() ) )
				 ->setUnitAmount();
			$items[] = $item;
		}

		// set items in list
		$itemList = new PayPalApi\ItemList();
		$itemList->setItems( $items );

		// set order details
		$details = new PayPalApi\Details();
		$details->setTax( 0 )/** @todo add tax support later */
		        ->setSubtotal( $this->cents_to_full( $order->get_subtotal() ) );

		// set amount
		$amount = new PayPalApi\Amount();
		$amount->setCurrency( $currency )
		       ->setTotal( $this->cents_to_full( $order->get_total() ) )
			   ->setBreakdown()
		       ->setDetails( $details );

		// setup transactions
		$invoiceStr = $order->get_id();
		$invoice_prefix = $this->get_option( 'invoice_prefix' );
		if ( ! empty( $invoice_prefix ) ) {
			$invoiceStr = $this->get_option( 'invoice_prefix' ) . $invoiceStr;
		}

		// setup transaction object
		$transaction = new PayPalApi\Transaction();
		$transaction->setAmount( $amount )
		            ->setItemList( $itemList->getItems() )
		            ->setDescription( sprintf( "%s - Order #%d ", get_bloginfo( 'name' ), $order->get_id() ) )
		            ->setInvoiceNumber( $invoiceStr );
		
		return $transaction;
	}


	/**
	 * @param float $fl_cents
	 *
	 * @return int
	 */
	private function cents_to_full( $fl_cents ) {

		return number_format( ( $fl_cents / 100 ), 2, ".", "" );
	}
}