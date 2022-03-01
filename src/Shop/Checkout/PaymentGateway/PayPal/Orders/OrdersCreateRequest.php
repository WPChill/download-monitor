<?php

namespace Never5\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Orders;

use Never5\DownloadMonitor\Dependencies\PayPalHttp\HttpRequest;

class OrdersCreateRequest extends HttpRequest
{
    function __construct()
    {
        parent::__construct("/v2/checkout/orders?", "POST");
        $this->headers["Content-Type"] = "application/json";
    }


    public function payPalPartnerAttributionId($payPalPartnerAttributionId)
    {
        $this->headers["PayPal-Partner-Attribution-Id"] = $payPalPartnerAttributionId;
    }
    public function prefer($prefer)
    {
        $this->headers["Prefer"] = $prefer;
    }
}
