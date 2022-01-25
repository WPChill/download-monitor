<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;

/**
 * Class ShippingCost
 *
 * Shipping cost, as a percent or an amount.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Currency amount
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Tax tax
 */
class ShippingCost extends PayPalModel
{
    /**
     * The shipping cost, as an amount. Valid range is from 0 to 999999.99.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Currency $amount
     * 
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * The shipping cost, as an amount. Valid range is from 0 to 999999.99.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * The tax percentage on the shipping amount.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Tax $tax
     * 
     * @return $this
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
        return $this;
    }

    /**
     * The tax percentage on the shipping amount.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Tax
     */
    public function getTax()
    {
        return $this->tax;
    }

}
