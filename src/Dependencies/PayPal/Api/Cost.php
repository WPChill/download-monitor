<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;
use WPChill\DownloadMonitor\Dependencies\PayPal\Converter\FormatConverter;
use WPChill\DownloadMonitor\Dependencies\PayPal\Validation\NumericValidator;

/**
 * Class Cost
 *
 * Cost as a percent or an amount. For example, to specify 10%, enter `10`. Alternatively, to specify an amount of 5, enter `5`.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string percent
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Currency amount
 */
class Cost extends PayPalModel
{
    /**
     * Cost in percent. Range of 0 to 100.
     *
     * @param string $percent
     * 
     * @return $this
     */
    public function setPercent($percent)
    {
        NumericValidator::validate($percent, "Percent");
        $percent = FormatConverter::formatToNumber($percent);
        $this->percent = $percent;
        return $this;
    }

    /**
     * Cost in percent. Range of 0 to 100.
     *
     * @return string
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * The cost, as an amount. Valid range is from 0 to 1,000,000.
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
     * The cost, as an amount. Valid range is from 0 to 1,000,000.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
