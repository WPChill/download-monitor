<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;

/**
 * Class Transactions
 *
 * 
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Amount amount
 */
class Transactions extends PayPalModel
{
    /**
     * Amount being collected.
     * 
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Amount $amount
     * 
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Amount being collected.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
