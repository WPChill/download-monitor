<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal;

use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Orders\OrdersCreateRequest;
use WPChill\DownloadMonitor\Dependencies\PayPalHttp;

class CreateOrder
{
	private $client;

	private $payer;

	private $intent;

	private $transactions;

	private $redirectUrls;

	private $id;

	private $status;

	private $links;

	public function setClient( $client ) {
		$this->client = $client;
	}

	public function setPayer( $payer ) {
		$this->payer = $payer;
		return $this;
	}

	public function setIntent( $intent ) {
		$this->intent = $intent;
		return $this;
	}
	
	public function getIntent() {
		return $this->intent;
	}
	
	public function setTransactions( $transactions ) {
		$this->transactions = $transactions;
		return $this;
	}
	
	public function getTransactions() {
		return $this->transactions;
	}
	
	public function setRedirectUrls( $redirectUrls ) {
		$this->redirectUrls = $redirectUrls;
		return $this;
	}

	private function setId( $id ) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	private function setStatus( $status ) {
		$this->status = $status;
	}

	public function getStatus() {
		return $this->status;
	}

	private function setLinks( $links ) {
		$this->links = $links;
	}

	public function getLinks() {
		return $this->links;
	}

	public function getApprovalLink() {
		$links = $this->getLinks();
		
		if ( isset( $links['approve'] ) ) {
			return $links['approve'];
		}

		return '';
	}

	/**
	 * Setting up the JSON request body.
	 */
	private function buildRequestBody() {
		return array(
			'intent' => $this->getIntent(),
			'application_context' => array(
				'return_url' => $this->redirectUrls->getReturnUrl(),
				'cancel_url' => $this->redirectUrls->getCancelUrl(),
				'user_action' => 'PAY_NOW',
			),
			'purchase_units' => $this->getTransactions(),
		);
	}

	/**
	 * This is the sample function which can be used to create an order. It uses the
	 * JSON body returned by buildRequestBody() to create an new Order.
	 */
	public function createOrder() {
		$request = new OrdersCreateRequest();
		$request->headers["prefer"] = "return=representation";
		$request->body = $this->buildRequestBody();

		try {

			$response = $this->client->execute($request);

			$this->setId( $response->result->id );
			$this->setStatus( $response->result->status );
			$links = array();
			foreach ($response->result->links as $link) {
				$links[ $link->rel ] = $link->href;
			}
			$this->setLinks( $links );

		} catch ( PayPalHttp\HttpException $ex ) {

			// echo $ex->statusCode;
			// print_r($ex->getMessage());

		}

		return $response;
	}

}
