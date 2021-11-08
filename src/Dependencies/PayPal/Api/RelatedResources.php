<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;

/**
 * Class RelatedResources
 *
 * Each one representing a financial transaction (Sale, Authorization, Capture, Refund) related to the payment.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Sale sale
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Authorization authorization
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Order order
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Capture capture
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Refund refund
 */
class RelatedResources extends PayPalModel
{
    /**
     * Sale transaction
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Sale $sale
     * 
     * @return $this
     */
    public function setSale($sale)
    {
        $this->sale = $sale;
        return $this;
    }

    /**
     * Sale transaction
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Sale
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * Authorization transaction
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Authorization $authorization
     * 
     * @return $this
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;
        return $this;
    }

    /**
     * Authorization transaction
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Authorization
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Order transaction
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Order $order
     * 
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Order transaction
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Capture transaction
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Capture $capture
     * 
     * @return $this
     */
    public function setCapture($capture)
    {
        $this->capture = $capture;
        return $this;
    }

    /**
     * Capture transaction
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Capture
     */
    public function getCapture()
    {
        return $this->capture;
    }

    /**
     * Refund transaction
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Refund $refund
     * 
     * @return $this
     */
    public function setRefund($refund)
    {
        $this->refund = $refund;
        return $this;
    }

    /**
     * Refund transaction
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Refund
     */
    public function getRefund()
    {
        return $this->refund;
    }

}
