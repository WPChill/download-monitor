<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Api;

/**
 * Class PayerInfo
 *
 * A resource representing a information about Payer.
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string email_address
 * @property string external_remember_me_id
 * @property string buyer_account_number
 * @property string name
 * @property string salutation
 * @property string first_name
 * @property string middle_name
 * @property string last_name
 * @property string suffix
 * @property string payer_id
 * @property string phone
 * @property string phone_type
 * @property string birth_date
 * @property string tax_id
 * @property string tax_id_type
 * @property string country_code
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Address billing_address
 */
class PayerInfo
{

	public $email_address;
	public $name;
	public $billing_address;
    /**
     * Email address representing the payer. 127 characters max.
     *
     * @param string $email_address
     * 
     * @return $this
     */
    public function setEmailAddress($email_address)
    {
        $this->email_address = $email_address;
        return $this;
    }

    /**
     * Email address representing the payer. 127 characters max.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email_address;
    }

    /**
     * External Remember Me id representing the payer
     *
     * @param string $external_remember_me_id
     * 
     * @return $this
     */
    public function setExternalRememberMeId($external_remember_me_id)
    {
        $this->external_remember_me_id = $external_remember_me_id;
        return $this;
    }

    /**
     * External Remember Me id representing the payer
     *
     * @return string
     */
    public function getExternalRememberMeId()
    {
        return $this->external_remember_me_id;
    }

    /**
     * Account Number representing the Payer
     *
     * @deprecated Use #setBuyerAccountNumberInstead
     * @param string $account_number
     *
     * @return $this
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
        return $this;
    }

    /**
     * Account Number representing the Payer
     *
     * @deprecated Use #getBuyerAccountNumberInstead
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * Account Number representing the Payer
     *
     * @param string $buyer_account_number
     * 
     * @return $this
     */
    public function setBuyerAccountNumber($buyer_account_number)
    {
        $this->buyer_account_number = $buyer_account_number;
        return $this;
    }

    /**
     * Account Number representing the Payer
     *
     * @return string
     */
    public function getBuyerAccountNumber()
    {
        return $this->buyer_account_number;
    }

	/**
	 * Full name
	 */
	public function setName($first_name, $last_name) {
		$this->name = array(
			'given_name' => $first_name,
			'surname' => $last_name
		);
	}

    /**
     * Salutation of the payer.
     *
     * @param string $salutation
     * 
     * @return $this
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
        return $this;
    }

    /**
     * Salutation of the payer.
     *
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * First name of the payer.
     *
     * @param string $first_name
     * 
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * First name of the payer.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Middle name of the payer.
     *
     * @param string $middle_name
     * 
     * @return $this
     */
    public function setMiddleName($middle_name)
    {
        $this->middle_name = $middle_name;
        return $this;
    }

    /**
     * Middle name of the payer.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middle_name;
    }

    /**
     * Last name of the payer.
     *
     * @param string $last_name
     * 
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Last name of the payer.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Suffix of the payer.
     *
     * @param string $suffix
     * 
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Suffix of the payer.
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * PayPal assigned encrypted Payer ID.
     *
     * @param string $payer_id
     * 
     * @return $this
     */
    public function setPayerId($payer_id)
    {
        $this->payer_id = $payer_id;
        return $this;
    }

    /**
     * PayPal assigned encrypted Payer ID.
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * Phone number representing the payer. 20 characters max.
     *
     * @param string $phone
     * 
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Phone number representing the payer. 20 characters max.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Phone type
     * Valid Values: ["HOME", "WORK", "MOBILE", "OTHER"]
     *
     * @param string $phone_type
     * 
     * @return $this
     */
    public function setPhoneType($phone_type)
    {
        $this->phone_type = $phone_type;
        return $this;
    }

    /**
     * Phone type
     *
     * @return string
     */
    public function getPhoneType()
    {
        return $this->phone_type;
    }

    /**
     * Birth date of the Payer in ISO8601 format (yyyy-mm-dd).
     *
     * @param string $birth_date
     * 
     * @return $this
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    /**
     * Birth date of the Payer in ISO8601 format (yyyy-mm-dd).
     *
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * Payer’s tax ID. Only supported when the `payment_method` is set to `paypal`.
     *
     * @param string $tax_id
     * 
     * @return $this
     */
    public function setTaxId($tax_id)
    {
        $this->tax_id = $tax_id;
        return $this;
    }

    /**
     * Payer’s tax ID. Only supported when the `payment_method` is set to `paypal`.
     *
     * @return string
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * Payer’s tax ID type. Allowed values: `BR_CPF` or `BR_CNPJ`. Only supported when the `payment_method` is set to `paypal`.
     * Valid Values: ["BR_CPF", "BR_CNPJ"]
     *
     * @param string $tax_id_type
     * 
     * @return $this
     */
    public function setTaxIdType($tax_id_type)
    {
        $this->tax_id_type = $tax_id_type;
        return $this;
    }

    /**
     * Payer’s tax ID type. Allowed values: `BR_CPF` or `BR_CNPJ`. Only supported when the `payment_method` is set to `paypal`.
     *
     * @return string
     */
    public function getTaxIdType()
    {
        return $this->tax_id_type;
    }

    /**
     * Two-letter registered country code of the payer to identify the buyer country.
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
     * Two-letter registered country code of the payer to identify the buyer country.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Billing address of the Payer.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Address $billing_address
     * 
     * @return $this
     */
    public function setBillingAddress($billing_address)
    {
        $this->billing_address = $billing_address;
        return $this;
    }

    /**
     * Billing address of the Payer.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Address
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

    /**
     * @deprecated [DEPRECATED] Use shipping address present in purchase unit or at root level of checkout Session.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ShippingAddress $shipping_address
     * 
     * @return $this
     */
    public function setShippingAddress($shipping_address)
    {
        $this->shipping_address = $shipping_address;
        return $this;
    }

    /**
     * @deprecated  [DEPRECATED] Use shipping address present in purchase unit or at root level of checkout Session.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ShippingAddress
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

}
