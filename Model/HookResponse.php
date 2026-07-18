<?php

namespace Netpay\Payment\Model;

/**
 * Class HookResponse
 */
class HookResponse
{
    const ERR_INSUFFICIENT_INFORMATION     = 6200;
    const ERR_CODE_INVALID                 = 6201;
    const ERR_CODE_EXPIRED                 = 6202;
    const ERR_CODE_NOT_AVAILABLE           = 6203;
    const ERR_CODE_LIMIT_REACHED           = 6204;
    const ERR_MINIMUM_CART_AMOUNT_REQUIRED = 6205;
    const ERR_UNIQUE_EMAIL_REQUIRED        = 6206;
    const ERR_ITEMS_NOT_ELIGIBLE           = 6207;
    const ERR_SERVICE                      = 6001;
    const ERR_PPC_OUT_OF_STOCK             = 6301;
    const ERR_PPC_INVALID_QUANTITY         = 6303;
    const SUCCESS                          = 200;
    
    /**
     * @param       $statusCode
     * @param       $message
     * @param array $additionalData
     * @return array
     */
    public function prepareResponseMessage($statusCode, $message, $additionalData = [])
    {
        if ($statusCode == 200) {
            $response = [
                'status' => 'success',
                'message' => $message
            ];
        } else {
            $response = [
                'status' => 'failure',
                'error' => [
                    'code' => $statusCode,
                    'message' => $message,
                ],
            ];
        }

        if (count($additionalData)) {
            $response += $additionalData;
        }

        return $response;
    }
}