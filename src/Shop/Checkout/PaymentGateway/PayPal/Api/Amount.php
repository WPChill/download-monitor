<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Api;

/**
 * Class Amount
 *
 * payment amount with break-ups.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string currency_code
 * @property string amount
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Details details
 */
class Amount
{
    /**
     * 3-letter [currency code](https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/). PayPal does not support all currencies.
     *
     * @param string $currency
     * 
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency_code = $currency;
        return $this;
    }

    /**
     * 3-letter [currency code](https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/). PayPal does not support all currencies.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency_code;
    }

    /**
     * Total amount charged from the payer to the payee. In case of a refund, this is the refunded amount to the original payer from the payee. 10 characters max with support for 2 decimal places.
     *
     * @param string|double $total
     * 
     * @return $this
     */
    public function setTotal($total)
    {
        NumericValidator::validate($total, "Total");
        $total = FormatConverter::formatToPrice($total, $this->getCurrency());
        // $this->total = $total;
        // V2
		$this->value = $total;
        return $this;
    }

    /**
     * Total amount charged from the payer to the payee. In case of a refund, this is the refunded amount to the original payer from the payee. 10 characters max with support for 2 decimal places.
     *
     * @return string
     */
    public function getTotal()
    {
        // return $this->total;
        // V2
		return $this->value;
    }

	public function setBreakdown() {
		$this->breakdown = array(
			'item_total' => array(
				'currency_code' => $this->currency_code,
				'value' => $this->getTotal(),
			),
			'shipping' => array(
				'currency_code' => $this->currency_code,
				'value' => '0.00',
			),
			'handling' => array(
				'currency_code' => $this->currency_code,
				'value' => '0.00',
			),
			'tax_total' => array(
				'currency_code' => $this->currency_code,
				'value' => '0.00',
			),
			'shipping_discount' => array(
				'currency_code' => $this->currency_code,
				'value' => '0.00',
			),			
		);
		return $this;
	}

	public function getBreakdown() {
	}

    /**
     * Additional details of the payment amount.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Details $details
     * 
     * @return $this
     */
    public function setDetails($details)
    {
        // $this->details = $details;
        return $this;
    }

    /**
     * Additional details of the payment amount.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Details
     */
    public function getDetails()
    {
        return $this->details;
    }

}
