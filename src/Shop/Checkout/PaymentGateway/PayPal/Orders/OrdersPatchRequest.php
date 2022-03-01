<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Orders;

use WPChill\DownloadMonitor\Dependencies\PayPalHttp\HttpRequest;

class OrdersPatchRequest extends HttpRequest
{
    function __construct($orderId)
    {
        parent::__construct("/v2/checkout/orders/{order_id}?", "PATCH");

        $this->path = str_replace("{order_id}", urlencode($orderId), $this->path);
        $this->headers["Content-Type"] = "application/json";
    }



}
