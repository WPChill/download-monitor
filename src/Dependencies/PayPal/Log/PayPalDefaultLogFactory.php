<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Log;

use WPChill\DownloadMonitor\Dependencies\Psr\Log\LoggerInterface;

/**
 * Class PayPalDefaultLogFactory
 *
 * This factory is the default implementation of Log factory.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Log
 */
class PayPalDefaultLogFactory implements PayPalLogFactory
{
    /**
     * Returns logger instance implementing LoggerInterface.
     *
     * @param string $className
     * @return LoggerInterface instance of logger object implementing LoggerInterface
     */
    public function getLogger($className)
    {
        return new PayPalLogger($className);
    }
}
