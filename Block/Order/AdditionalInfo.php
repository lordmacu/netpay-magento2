<?php

namespace Netpay\Payment\Block\Order;

use \Magento\Framework\View\Element\Template\Context;

class AdditionalInfo extends \Magento\Framework\View\Element\Template
{ 
    protected $_checkoutSession;
    protected $_currency;

    public function __construct(
    	Context $context,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {  
        parent::__construct($context); 
        $this->_currency = $currency;     
        $this->_checkoutSession = $checkoutSession;       
    }

    public function getAdditionalInfo()
    {
        return $this->_checkoutSession->getAdditionalInfo();
    }

    public function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }
    /**
     * @return string
    */
    public function getImageUrl(): string
    {
        return  $this->getViewFileUrl("Netpay_Payment::images/logos_efectivo.svg");
    }
    /**
     * @return string
    */
    public function getSuccessImageUrl(): string
    {
        return  $this->getViewFileUrl("Netpay_Payment::images/logos_success.png");
    }
    /**
     * @return string
    */
    public function getOxxopayUrl(): string
    {
        return  $this->getViewFileUrl("Netpay_Payment::images/oxxo_pay.svg");
    }

    /**
     * @return string
    */
    public function getOndaUrl(): string
    {
        return  $this->getViewFileUrl("Netpay_Payment::images/back.svg");
    }

    /**
     * @return string
    */
    public function getSuccessOxxoPayUrl(): string
    {
        return  $this->getViewFileUrl("Netpay_Payment::images/sucess_checkout.svg");
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */    
    public function getCurrentCurrencySymbol()
    {
        // Prefer the order's own currency; the injected Currency model has no code set and
        // would return an empty/incorrect symbol on the receipt.
        $order = $this->getOrder();
        if ($order && $order->getOrderCurrency()) {
            return $order->getOrderCurrency()->getCurrencySymbol();
        }

        return $this->_currency->getCurrencySymbol();
    }
    
} 