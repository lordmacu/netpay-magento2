<?php

/**
 * All Magento function which can be necessary
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Magento;

class Shopsystem 
{
    /**
     * call functions from Magento to get all cartinformations
     * in Magento we use $order as $cart
     * 
     * @param object $cart
     * @param \stdClass|array|null $otherData
     * 
     * @return object
     */
    public static function prepareShopCartObj($cart, $otherData)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
        $customer = $objectManager->create('Magento\Customer\Model\Customer');
        if (count($cart->getAllVisibleItems()) > 0) {
            foreach ($cart->getAllVisibleItems() as $item) {
                $cart->products[] = $item->getData();
            }
        }

        if ($cart->getCustomer() && $cart->getCustomer()->getId()) {
            $cart->custom = $customer->load($cart->getCustomer()->getId())->getData();
            if ($cart->custom['gender']) {
                if ($cart->custom['gender'] == '1') {
                    $cart->gender = 'M';
                } elseif ($cart->custom['gender'] == '2') {
                    $cart->gender = 'F';
                } else {
                    $cart->gender = '';
                }
            }
            if ($cart->custom['dob']) {
                $newDate = date("d M Y", strtotime($cart->custom['dob']));  
                $cart->custom['dob'] = $newDate;
            }
        } else {
            $cart->custom = ['email' => $cart->getCustomerEmail()];
        }

        $cart->total = $cart->getGrandTotal();
        $integerTotal = round((float)$cart->getGrandTotal(), 2);
        $cart->integerTotal = (int)round($integerTotal * 100);

        $shippingAddress = $cart->getShippingAddress();
        $deliveryAddress = $shippingAddress ? $shippingAddress->getData() : [];
        $cart->invoiceAddress = $cart->getBillingAddress()->getData();
        if (empty($deliveryAddress['street']) &&
            empty($deliveryAddress['city']) &&
            empty($deliveryAddress['region']) &&
            empty($deliveryAddress['postcode'])
        ) {
            $cart->deliveryMethod = 'Email';
        } else {
            $cart->deliveryAddress = $deliveryAddress;
            $deliveryAddress['city'] = empty($deliveryAddress['city']) ? '-' : $deliveryAddress['city'];
            $deliveryAddress['postcode'] = empty($deliveryAddress['postcode']) ? '-' : $deliveryAddress['postcode'];
            if (!empty($cart->deliveryAddress['region'])) {
                $regionDelivery = $objectManager->create('Magento\Directory\Model\ResourceModel\Region\Collection')
                    ->addRegionNameFilter($cart->deliveryAddress['region'])
                    ->getFirstItem()
                    ->toArray();
                $cart->deliveryAddress['regioncode'] = (isset($regionDelivery['code'])) ? $regionDelivery['code'] : $cart->deliveryAddress['region'];
            } else {
                $cart->deliveryAddress['region'] = '-';
                $cart->deliveryAddress['regioncode'] = '-';
            }
            if (!empty($cart->deliveryAddress['street'])) {
                $linesDelivery = explode("\n", $cart->deliveryAddress['street']);
                $cart->deliveryAddress['line1'] = $linesDelivery[0];
                $linesDelivery2 = (isset($linesDelivery[1])) ? $linesDelivery[1] : '';
                if (isset($linesDelivery[2])) {
                    $linesDelivery2 .= ' ' . $linesDelivery[2];
                }
                $cart->deliveryAddress['line2'] = $linesDelivery2;
            } else {
                $cart->deliveryAddress['line1'] = '-';
            }
        }

        $billingAddress = array_diff($cart->invoiceAddress, $deliveryAddress);

        $cart->isoCurrency = $currency;
        $cart->cartId = $cart->getId();
        $cart->source = 'Magento';

        if (!empty($cart->invoiceAddress['region'])) {
            $regionInvoice = $objectManager->create('Magento\Directory\Model\ResourceModel\Region\Collection')
                ->addRegionNameFilter($cart->invoiceAddress['region'])
                ->getFirstItem()
                ->toArray();
            $cart->invoiceAddress['regioncode'] = (isset($regionInvoice['code'])) ? $regionInvoice['code'] : $cart->invoiceAddress['region'];
        } else {
            $cart->deliveryAddress['region'] = '-';
            $cart->deliveryAddress['regioncode'] = '-';
        }
        
        $linesInvoice = explode("\n", $cart->invoiceAddress['street']);
        $cart->invoiceAddress['line1'] = $linesInvoice[0];
        $linesInvoice2 = (isset($linesInvoice[1])) ? $linesInvoice[1] : '';
        if (isset($linesInvoice[2])) {
            $linesInvoice2 .= ' ' . $linesInvoice[2];
        }
        $cart->invoiceAddress['line2'] = $linesInvoice2;
        
        if ($otherData->paymentmethod == 'cash') {
            $cashObj = new \stdClass();
            $cashObj->invoiceAddress['firstname'] = $cart->invoiceAddress['firstname'];
            $cashObj->invoiceAddress['lastname'] = $cart->invoiceAddress['lastname'];
            $cashObj->custom['email'] = $cart->custom['email'];     
            $cashObj->total = $cart->total;
            $cashObj->isoCurrency = $cart->isoCurrency;
            $cashObj->invoiceAddress['telephone'] = $cart->invoiceAddress['telephone'];
            $cashObj->cartId = $cart->getId();
            
            return $cashObj;
        }
        
        return $cart;
    }
}
