<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPal\Api;

use WPChill\DownloadMonitor\Dependencies\PayPal\Common\PayPalModel;

/**
 * Class InvoiceNumber
 *
 * The next invoice number
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string number
 */
class InvoiceNumber extends PayPalModel
{
    /**
     * The next invoice number.
     *
     * @param string $number
     *
     * @return $this
     */
    public function setNumber($number) {
        $this->number = $number;
        return $this;
    }

    /**
     * The next invoice number.
     *
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }
}
