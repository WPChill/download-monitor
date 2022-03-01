<?php

namespace Never5\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Orders;

use Never5\DownloadMonitor\Dependencies\PayPalHttp\HttpRequest;

class OrdersValidateRequest extends HttpRequest
{
    function __construct($orderId)
    {
        parent::__construct("/v2/checkout/orders/{order_id}/validate-payment-method?", "POST");

        $this->path = str_replace("{order_id}", urlencode($orderId), $this->path);
        $this->headers["Content-Type"] = "application/json";
    }


    public function payPalClientMetadataId($payPalClientMetadataId)
    {
        $this->headers["PayPal-Client-Metadata-Id"] = $payPalClientMetadataId;
    }
}
