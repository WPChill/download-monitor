<?php

namespace Never5\DownloadMonitor\Dependencies\PayPalHttp;

/**
 * Interface Injector
 * @package Never5\DownloadMonitor\Dependencies\PayPalHttp
 *
 * Interface that can be implemented to apply injectors to Http client.
 *
 * @see HttpClient
 */
interface Injector
{
    /**
     * @param HttpRequest $httpRequest
     */
    public function inject($httpRequest);
}
