<?php

namespace Never5\DownloadMonitor\Shop\Dependencies\PayPal\Handler;

/**
 * Interface IPayPalHandler
 *
 * @package Never5\DownloadMonitor\Shop\Dependencies\PayPal\Handler
 */
interface IPayPalHandler
{
    /**
     *
     * @param \Paypal\Core\PayPalHttpConfig $httpConfig
     * @param string $request
     * @param mixed $options
     * @return mixed
     */
    public function handle($httpConfig, $request, $options);
}
