<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Core;

use WPChill\DownloadMonitor\Dependencies\PayPalHttp\HttpClient;

class PayPalHttpClient extends HttpClient
{
    private $refreshToken;
    public $authInjector;

    public function __construct(PayPalEnvironment $environment, $refreshToken = NULL)
    {
        parent::__construct($environment);
        $this->refreshToken = $refreshToken;
        $this->authInjector = new AuthorizationInjector($this, $environment, $refreshToken);
        $this->addInjector($this->authInjector);
        $this->addInjector(new GzipInjector());
        $this->addInjector(new FPTIInstrumentationInjector());
    }

    public function userAgent()
    {
        return UserAgent::getValue();
    }
}

