<?php

/**
 * Here are all custom function are changeable if necessary
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\CustomLayer;

class Customizations
{
    
    /**
     * return the authorization string which should be in request header
     *
     * @param array $authParams   Is the assoziative array with the informations for Authorization
     *                            Is coming from Shopsystem or config.ini File
     * @param array $requestArray Is the parameters in body which we will send to APi. Sometimes we need
     *                            this values for signature
     *
     * @return string
     */
    public static function customSignatureFunction($authParams, $requestArray, $section)
    {
        //$body = json_encode($this->requestArray);
        //@Todo Insert you custom logic
        if ($section == 'Configuration') {
            $password = ($authParams['mode'] == 'live') ? $authParams['keylive'] : $authParams['keytest'];
        } else {
            $password = ($authParams['mode'] == 'live') ? $authParams['passwordlive'] : $authParams['passwordtest'];
        }
        return $password;
    }
    
    /**
     * calculate and insert signature attribute in request array
     * 
     * @param array $requestArray
     * @param \BusinessLayer\Netpay\Utilities\DicUnit[] $dictonary
     * @param string $value  
     * @param string $password
     * 
     * @return array
     */
    public static function customBodySignatureFunction($requestArray, $dictonary, $value, $password = '')
    {
        //@Todo Insert you custom logic
        //$requestArray['signature'] = $signatureattribute;
        //return $requestArray;
    }
}
