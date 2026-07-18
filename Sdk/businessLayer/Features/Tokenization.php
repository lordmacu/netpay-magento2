<?php

/**
 * Create an Order and get a tokenresponse
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Features;

class Tokenization extends Feature
{
    /**
     * create a Feature object with values from config files/$configParams and the 
     * PaymentManager object. This means the Request header and body, Apiinstance and 
     * debug files will created with section Tokenization in config file and Attributes 
     * from object PaymentManager
     *
     * @param \BusinessLayer\Netpay\PaymentManager $paymentManager 
     * @param array|null $configParams
     */
    public function __construct($paymentManager, $configParams = null)
    {
        parent::__construct('Tokenisation', $configParams, $paymentManager);
    }
}
