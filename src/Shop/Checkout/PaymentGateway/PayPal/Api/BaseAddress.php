<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Api;

/**
 * Class BaseAddress
 *
 * Base Address object used as billing address in a payment or extended for Shipping Address.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 * 
 * @link https://developer.paypal.com/api/orders/v2/#definition-address_portable
 * 
 * @property string address_line_1
 * @property string address_line_2
 * @property string address_line_3
 * @property string admin_area_4
 * @property string admin_area_3
 * @property string admin_area_2	// city
 * @property string admin_area_1	// state
 * @property string postal_code
 * @property string country_code
 * @property string normalization_status
 * @property string status
 */
class BaseAddress
{
    /**
     * Line 1 of the Address (eg. number, street, etc).
     *
     * @param string $line1
     * 
     * @return $this
     */
    public function setLine1($line1)
    {
        $this->address_line_1 = $line1;
        return $this;
    }

    /**
     * Line 1 of the Address (eg. number, street, etc).
     *
     * @return string
     */
    public function getLine1()
    {
        return $this->address_line_1;
    }

    /**
     * Optional line 2 of the Address (eg. suite, apt #, etc.).
     *
     * @param string $line2
     * 
     * @return $this
     */
    public function setLine2($line2)
    {
        $this->address_line_2 = $line2;
        return $this;
    }

    /**
     * Optional line 2 of the Address (eg. suite, apt #, etc.).
     *
     * @return string
     */
    public function getLine2()
    {
        return $this->address_line_2;
    }

    /**
     * City name.
     *
     * @param string $city
     * 
     * @return $this
     */
    public function setCity($city)
    {
        $this->admin_area_2 = $city;
        return $this;
    }

    /**
     * City name.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->admin_area_2;
    }

    /**
     * Zip code or equivalent is usually required for countries that have them. For list of countries that do not have postal codes please refer to http://en.wikipedia.org/wiki/Postal_code.
     *
     * @param string $postal_code
     * 
     * @return $this
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
        return $this;
    }

    /**
     * Zip code or equivalent is usually required for countries that have them. For list of countries that do not have postal codes please refer to http://en.wikipedia.org/wiki/Postal_code.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * 2 letter country code.
     *
     * @param string $country_code
     * 
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * 2 letter country code.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * 2 letter code for US states, and the equivalent for other countries.
     *
     * @param string $state
     * 
     * @return $this
     */
    public function setState($state)
    {
        $this->admin_area_1 = $state;
        return $this;
    }

    /**
     * 2 letter code for US states, and the equivalent for other countries.
     *
     * @return string
     */
    public function getState()
    {
        return $this->admin_area_1;
    }

    /**
     * Address normalization status
     * Valid Values: ["UNKNOWN", "UNNORMALIZED_USER_PREFERRED", "NORMALIZED", "UNNORMALIZED"]
     *
     * @param string $normalization_status
     *
     * @return $this
     */
    public function setNormalizationStatus($normalization_status)
    {
        $this->normalization_status = $normalization_status;
        return $this;
    }

    /**
     * Address normalization status
     *
     * @return string
     */
    public function getNormalizationStatus()
    {
        return $this->normalization_status;
    }

    /**
     * Address status
     * Valid Values: ["CONFIRMED", "UNCONFIRMED"]
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Address status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

}
