<?php

namespace WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PayPal\Api;

/**
 * Class CartBase
 *
 * Base properties of a cart resource
 *
 * @package WPChill\DownloadMonitor\Dependencies\PayPal\Api
 *
 * @property string reference_id
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Amount amount
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Payee payee
 * @property string description
 * @property string note_to_payee
 * @property string custom
 * @property string invoice_number
 * @property string purchase_order
 * @property string soft_descriptor
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\PaymentOptions payment_options
 * @property \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ItemList item_list
 * @property string notify_url
 * @property string order_url
 */
class CartBase
{
	public $amount;
	public $items;
	public $description;
	public $invoice_number;

    /**
     * Merchant identifier to the purchase unit. Optional parameter
     *
     * @param string $reference_id
     * 
     * @return $this
     */
    public function setReferenceId($reference_id)
    {
        $this->reference_id = $reference_id;
        return $this;
    }

    /**
     * Merchant identifier to the purchase unit. Optional parameter
     *
     * @return string
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * Amount being collected.
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

    /**
     * Recipient of the funds in this transaction.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Payee $payee
     * 
     * @return $this
     */
    public function setPayee($payee)
    {
        $this->payee = $payee;
        return $this;
    }

    /**
     * Recipient of the funds in this transaction.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\Payee
     */
    public function getPayee()
    {
        return $this->payee;
    }

    /**
     * Description of what is being paid for.
     *
     * @param string $description
     * 
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of what is being paid for.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Note to the recipient of the funds in this transaction.
     *
     * @param string $note_to_payee
     * 
     * @return $this
     */
    public function setNoteToPayee($note_to_payee)
    {
        $this->note_to_payee = $note_to_payee;
        return $this;
    }

    /**
     * Note to the recipient of the funds in this transaction.
     *
     * @return string
     */
    public function getNoteToPayee()
    {
        return $this->note_to_payee;
    }

    /**
     * free-form field for the use of clients
     *
     * @param string $custom
     * 
     * @return $this
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;
        return $this;
    }

    /**
     * free-form field for the use of clients
     *
     * @return string
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * invoice number to track this payment
     *
     * @param string $invoice_number
     * 
     * @return $this
     */
    public function setInvoiceNumber($invoice_number)
    {
        $this->invoice_number = $invoice_number;
        return $this;
    }

    /**
     * invoice number to track this payment
     *
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoice_number;
    }

    /**
     * purchase order is number or id specific to this payment
     *
     * @param string $purchase_order
     * 
     * @return $this
     */
    public function setPurchaseOrder($purchase_order)
    {
        $this->purchase_order = $purchase_order;
        return $this;
    }

    /**
     * purchase order is number or id specific to this payment
     *
     * @return string
     */
    public function getPurchaseOrder()
    {
        return $this->purchase_order;
    }

    /**
     * Soft descriptor used when charging this funding source. If length exceeds max length, the value will be truncated
     *
     * @param string $soft_descriptor
     * 
     * @return $this
     */
    public function setSoftDescriptor($soft_descriptor)
    {
        $this->soft_descriptor = $soft_descriptor;
        return $this;
    }

    /**
     * Soft descriptor used when charging this funding source. If length exceeds max length, the value will be truncated
     *
     * @return string
     */
    public function getSoftDescriptor()
    {
        return $this->soft_descriptor;
    }

    /**
     * Soft descriptor city used when charging this funding source. If length exceeds max length, the value will be truncated. Only supported when the `payment_method` is set to `credit_card`
     * @deprecated Not publicly available
     * @param string $soft_descriptor_city
     * 
     * @return $this
     */
    public function setSoftDescriptorCity($soft_descriptor_city)
    {
        $this->soft_descriptor_city = $soft_descriptor_city;
        return $this;
    }

    /**
     * Soft descriptor city used when charging this funding source. If length exceeds max length, the value will be truncated. Only supported when the `payment_method` is set to `credit_card`
     * @deprecated Not publicly available
     * @return string
     */
    public function getSoftDescriptorCity()
    {
        return $this->soft_descriptor_city;
    }

    /**
     * Payment options requested for this purchase unit
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\PaymentOptions $payment_options
     * 
     * @return $this
     */
    public function setPaymentOptions($payment_options)
    {
        $this->payment_options = $payment_options;
        return $this;
    }

    /**
     * Payment options requested for this purchase unit
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\PaymentOptions
     */
    public function getPaymentOptions()
    {
        return $this->payment_options;
    }

    /**
     * List of items being paid for.
     *
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ItemList $item_list
     * 
     * @return $this
     */
    public function setItemList($item_list)
    {
        // $this->item_list = $item_list;
        $this->items = $item_list;
        return $this;
    }

    /**
     * List of items being paid for.
     *
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ItemList
     */
    public function getItemList()
    {
        // return $this->item_list;
        return $this->items;
    }

    /**
     * URL to send payment notifications
     *
     * @param string $notify_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setNotifyUrl($notify_url)
    {
        UrlValidator::validate($notify_url, "NotifyUrl");
        $this->notify_url = $notify_url;
        return $this;
    }

    /**
     * URL to send payment notifications
     *
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notify_url;
    }

    /**
     * Url on merchant site pertaining to this payment.
     *
     * @param string $order_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setOrderUrl($order_url)
    {
        UrlValidator::validate($order_url, "OrderUrl");
        $this->order_url = $order_url;
        return $this;
    }

    /**
     * Url on merchant site pertaining to this payment.
     *
     * @return string
     */
    public function getOrderUrl()
    {
        return $this->order_url;
    }

    /**
     * List of external funding being applied to the purchase unit. Each external_funding unit should have a unique reference_id
     * @deprecated Not publicly available
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ExternalFunding[] $external_funding
     *
     * @return $this
     */
    public function setExternalFunding($external_funding)
    {
        $this->external_funding = $external_funding;
        return $this;
    }

    /**
     * List of external funding being applied to the purchase unit. Each external_funding unit should have a unique reference_id
     * @deprecated Not publicly available
     * @return \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ExternalFunding[]
     */
    public function getExternalFunding()
    {
        return $this->external_funding;
    }

    /**
     * Append ExternalFunding to the list.
     * @deprecated Not publicly available
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ExternalFunding $externalFunding
     * @return $this
     */
    public function addExternalFunding($externalFunding)
    {
        if (!$this->getExternalFunding()) {
            return $this->setExternalFunding(array($externalFunding));
        } else {
            return $this->setExternalFunding(
                array_merge($this->getExternalFunding(), array($externalFunding))
            );
        }
    }

    /**
     * Remove ExternalFunding from the list.
     * @deprecated Not publicly available
     * @param \WPChill\DownloadMonitor\Dependencies\PayPal\Api\ExternalFunding $externalFunding
     * @return $this
     */
    public function removeExternalFunding($externalFunding)
    {
        return $this->setExternalFunding(
            array_diff($this->getExternalFunding(), array($externalFunding))
        );
    }

}
