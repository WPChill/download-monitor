<?php

namespace Never5\DownloadMonitor\Shop\Dependencies\PayPal\Log;

use Never5\DownloadMonitor\Shop\Dependencies\Psr\Log\LoggerInterface;

interface PayPalLogFactory
{
    /**
     * Returns logger instance implementing LoggerInterface.
     *
     * @param string $className
     * @return LoggerInterface instance of logger object implementing LoggerInterface
     */
    public function getLogger($className);
}
