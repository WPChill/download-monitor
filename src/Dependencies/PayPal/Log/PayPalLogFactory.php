<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Log;

use WPChill\DownloadMonitor\Dependencies\Psr\Log\LoggerInterface;

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
