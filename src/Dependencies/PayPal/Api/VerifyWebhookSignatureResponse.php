<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;

/**
 * Class VerifyWebhookSignatureResponse
 *
 * The verify webhook signature response.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string verification_status
 */
class VerifyWebhookSignatureResponse extends PayPalModel
{
    /**
     * The status of the signature verification. Value is `SUCCESS` or `FAILURE`.
     * Valid Values: ["SUCCESS", "FAILURE"]
     *
     * @param string $verification_status
     * 
     * @return $this
     */
    public function setVerificationStatus($verification_status)
    {
        $this->verification_status = $verification_status;
        return $this;
    }

    /**
     * The status of the signature verification. Value is `SUCCESS` or `FAILURE`.
     *
     * @return string
     */
    public function getVerificationStatus()
    {
        return $this->verification_status;
    }

}
