<?php

/**
 * create Object with all data which can come from Shopsystem
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

class Shopdata 
{
    /** @var array */
    public $formData;
    /** @var object|array */
    public $cartData;
    /** @var \stdClass|array */
    public $otherData;
    /** @var \stdClass|array */
    public $backofficeConfigparam;
    /** @var object */
    public $prevResponseData;
    
    /**
     * create shopdata Object
     * 
     * @param array $shop                     Required. Name and version of Shopsystem. Is comimng
     *                                        from config file
     * @param object|array|null $cartData     Optional parameter. Is the Object or assoziative Array 
     *                                        from shop with all cart and checkout informations
     * @param object|array|null $cartData     Optional parameter. Is the Object or assoziative Array 
     *                                        from shop with all cart and checkout informations
     * @param \stdClass|array|null $otherData Optional parameter. This object or assoziative have 
     *                                        informations which is needed in the request but not in the 
     *                                        $cart from shopsystem e.g. cancle url or prepose response data
     * @param object|null $prevResponseData   Optional parameter. Paramters from previous response.
     *                                        E.g. Response of Tokenization
     * @param array|null $formData            Optional parameter. If we use additional form to select 
     *                                        special data for Customer (e.g. CreditCard data) then 
     *                                        we can pass the POST or GET Array formData
     * @param \stdClass|array|null $backofficeConfigparam Optional parameter. Can be different values like id,
     *                                                    endpointurl etc, which will needed for redirect or widget
     * 
     */
    public function __construct($shop, $cartData, $otherData, $prevResponseData, $formData, $backofficeConfigparam) 
    {
        if (!empty($cartData)) {
            if (method_exists('\\BusinessLayer\\Netpay\\' . $shop['name'] . '\\Shopsystem', 'prepareShopCartObj')) {
                $this->cartData = call_user_func('\\BusinessLayer\\Netpay\\' . $shop['name'] . '\\Shopsystem::prepareShopCartObj', $cartData, $otherData);
            } else {
           //@Todo
            }
        }
        //$this->cartData = $cartData;
        $this->otherData = $otherData;
        $this->formData = $formData;
        $this->backofficeConfigparam = $backofficeConfigparam;
        $this->prevResponseData = $prevResponseData;
    }
}