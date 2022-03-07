<?php

namespace Never5\DownloadMonitor\Dependencies\PayPalHttp;

/**
 * Interface Environment
 * @package Never5\DownloadMonitor\Dependencies\PayPalHttp
 *
 * Describes a domain that hosts a REST API, against which an HttpClient will make requests.
 * @see HttpClient
 */
interface Environment
{
    /**
     * @return string
     */
    public function baseUrl();
}
