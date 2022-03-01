<?php

namespace Never5\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Core;


use Never5\DownloadMonitor\Dependencies\PayPalHttp\Injector;

class GzipInjector implements Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->headers["Accept-Encoding"] = "gzip";
    }
}
