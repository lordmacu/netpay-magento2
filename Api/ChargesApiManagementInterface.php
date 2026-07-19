<?php

namespace Netpay\Payment\Api;

interface ChargesApiManagementInterface
{
    /**
     * get charges Api call.
     *
     * @api
     *
     * @param int    $referenceID
     * @param int    $orderId
     * @param string $paymentmethod
     * @param string $token
     * @param string $deviceInformation
     * @param int $msicount
     * @param bool $saveCc
     * @param string $cvv
     * @param bool $cardSelected
     * @param string $deviceFingerPrint
     *
     * @return string
     */
    public function getCharges($referenceID, $orderId, $paymentmethod, $token, $deviceInformation,  $msicount = null, $saveCc = false, $cvv = '', $cardSelected = false, $deviceFingerPrint = '');
}
