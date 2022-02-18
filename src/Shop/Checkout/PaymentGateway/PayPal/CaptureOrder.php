<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal;

use WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Orders\OrdersCaptureRequest;
use WPChill\DownloadMonitor\Dependencies\PayPalHttp;

class CaptureOrder {

	private $client;

	private $order_id;

	private $response;

	public function set_client( $client ) {
		$this->client = $client;
		return $this;
	}

	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	public function setResponse( $response ) {
		$this->response = $response;
	}

	public function getStatus() {
		return $this->response->result->status;
	}

	public function getId() {
		return $this->response->result->id;
	}

    /**
     * Below method can be used to build the capture request body.
     * This request can be updated with required fields as needed.
     * Refer to API specs for more info.
     */
    public function buildRequestBody() {
        return "{}";
    }

    /**
     * Below function can be used to capture order.
     * Valid Authorization id should be passed as an argument.
     */
    public function captureOrder() {
		try {

			$request = new OrdersCaptureRequest( $this->order_id );
			$request->body = self::buildRequestBody();
			$response = $this->client->execute( $request );

			$this->setResponse( $response );
			return $this;

		} catch ( PayPalHttp\HttpException $ex ) {

			//print_r($ex->getMessage());

		}
    }

}
