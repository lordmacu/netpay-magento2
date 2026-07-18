<?php

/**
 * All Prestashop function which can be necessary
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Prestashop;

use PrestaShop\PrestaShop\Adapter\Validate;

class Shopsystem 
{
    /**
     * call functions from prestashop to get all cartinformations
     * in Prestashop we use $this->context as $cartObj
     * 
     * @param object $cartObj
     * 
     * @return object
     */
    public static function prepareShopCartObj($cartObj)
    {
        $cart = $cartObj->cart;
        $cart->products = $cart->getProducts();
        $cart->custom = new \Customer($cart->id_customer);
        if (isset($cart->id_address_delivery) && !empty($cart->id_address_delivery)) {
            $cart->deliveryAddress = new \Address($cart->id_address_delivery);
            $cart->deliveryAddress->isocountry = $cartObj->country->iso_code;
            $cart->deliveryAddress->regioncode = '-';
            $state = new \State((int)$cart->deliveryAddress->id_state);
            if (Validate::isLoadedObject($state)) {
                $cart->deliveryAddress->region = $state->name;
                $cart->deliveryAddress->regioncode = (isset($state->iso_code) && $state->iso_code) ? $state->iso_code : $state->name;
            }
            $cart->deliveryAddress->address1 = empty($cart->deliveryAddress->address1) ? '-' : $cart->deliveryAddress->address1;
            $cart->deliveryAddress->city = empty($cart->deliveryAddress->city) ? '-' : $cart->deliveryAddress->city;
            $cart->deliveryAddress->postcode = empty($cart->deliveryAddress->postcode) ? '-' : $cart->deliveryAddress->postcode;
        } else {
            $cart->deliveryAddress->address1 = '-';
            $cart->deliveryAddress->regioncode = '-';
            $cart->deliveryAddress->city = '-';
            $cart->deliveryAddress->postcode = '-';
        }
        $cart->invoiceAddress = new \Address($cart->id_address_invoice);
        $cart->invoiceAddress->isocountry = $cartObj->country->iso_code;
        $stateInv = new \State((int)$cart->invoiceAddress->id_state);
        $cart->invoiceAddress->regioncode = '-';
        if (Validate::isLoadedObject($stateInv)) {
            $cart->invoiceAddress->region = $stateInv->name;
            $cart->invoiceAddress->regioncode = (isset($stateInv->iso_code) && $stateInv->iso_code) ? $stateInv->iso_code : $stateInv->name;
        }
        $cart->invoiceAddress->address1 = empty($cart->invoiceAddress->address1) ? '-' : $cart->invoiceAddress->address1;
        $cart->invoiceAddress->city = empty($cart->invoiceAddress->city) ? '-' : $cart->invoiceAddress->city;
        $cart->invoiceAddress->postcode = empty($cart->invoiceAddress->postcode) ? '-' : $cart->invoiceAddress->postcode;
        $cart->total = $cart->getOrderTotal(true, \Cart::BOTH);
        $integerTotal = round((float)$cart->total, 2);
        $cart->integerTotal = (int)($integerTotal * 100);
        $cart->isoCurrency = $cartObj->currency->iso_code;
        $cart->shopcountry = $cartObj->country->iso_code;
        $cart->source = 'Prestashop';
        
        return $cart;
    }
}