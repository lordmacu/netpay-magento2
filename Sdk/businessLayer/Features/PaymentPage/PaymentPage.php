<?php

/**
 * Generate a Paymentpage e.g. Widget or/and Redirect
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Features\PaymentPage;

use BusinessLayer\Netpay\Utilities\INIController;
use BusinessLayer\Netpay\Utilities\Constants;
use BusinessLayer\Netpay\Utilities\Mapping;

class PaymentPage
{
    /** @var string */
    public $jsFiles = "";
    /** @var array */
    public $templates = [];
    /** @var string */
    public $plugIntFilesFolderPath = "";
    /** @var array */
    public $mappingArray = [];
    /** @var array */
    public $callbackUrls = [];
    /** @var string */
    public $paymentpageFile = "";
    /** @var string */
    public $endpointUrl = "";

    /**
     * Return a PaymentPage object with information of given payment method.
     *
     * @param string $paymentmethod      Required. Type of Form which customer is using. We differentiate
     *                                   between 'redirect' and 'widget'. Redirect means all types where
     *                                   customer leaves the Shopsite, 'widget' means all types where
     *                                   custommer get Form without leaving shopsystem
     * @param boolean $jsIsUsed          Required. How is it implemented from paymentprovider?
     *                                   Do we get thirdparty js then jsUsed is true or do we send Get/Post
     *                                   params to paymentprovider then jsUsed is false
     * @param array $shop                Required. Shopsystem which is used. It is defined in config.ini
     * @param string|null $requestmethod Optional parameter. Required for paymentmethod 'redirect'.
     *                                   requestmethods can be 'GET' or 'POST'
     * @param BusinessLayer\Netpay\Utilities\Shopdata|null $shopData      Optional parameter. All Shopdata which is needed for a Request or form
     *
     * return BusinessLayer\Netpay\Features\PaymentPage\PaymentPage Return object of all information which is needed to proceed
     *                                                       with given payment method.
     *                                                       e.g. Widget, Redirect, Hosted Fields, 3D, ...
     */
    public function createPaymentPage($paymentmethod, $jsIsUsed, $shop, $requestmethod, $shopData)
    {
        $method = ucfirst($paymentmethod);
        $this->plugIntFilesFolderPath = $this->loadPlugIntFilesFolderPathFromConfig();
        $templateEngine = $this->getTemplateEngineByShopsystem($shop);
        
        if ($paymentmethod == 'widget' || $jsIsUsed) {
            //Callbackurls will used only in Widget. For Redirect it will set in preparing Request.
            $this->callbackUrls = $this->loadCallbackUrlsFromConfig('CallbackUrls');
            $this->templates = $this->loadFilesFromConfig($method .'Files');
            $jsFiles = $this->loadThirdpartyJsFilesFromConfig();
            $this->jsFiles = $jsFiles;
            $this->mappingArray = $this->loadMappingArrayFromApiConfig($method);
            $this->paymentpageFile = $this->plugIntFilesFolderPath . '/' . $templateEngine . '/widgetTemplate.' . $templateEngine;
        } else {
            $this->mappingArray = $this->mapToRequestArray($shopData, $method);
            $endpointUrl = $this->mappingArray[Constants::ENDPOINT_ATTRIBUTE_NAME];
            unset($this->mappingArray[Constants::ENDPOINT_ATTRIBUTE_NAME]);
            if ($requestmethod == 'GET') {
                $endpointUrl .= '?';
                foreach ($this->mappingArray as $name => $value) {
                    $endpointUrl .= $name . '=' . $value . '&';
                }
                $endpointUrl = substr($endpointUrl, 0, -1);
                $this->paymentpageFile = $this->plugIntFilesFolderPath . '/' . $templateEngine .'/redirectGetTemplate.' . $templateEngine;
            } else {
                $this->paymentpageFile = $this->plugIntFilesFolderPath . '/' . $templateEngine .'/redirectPostTemplate.' . $templateEngine;
            }
            $this->endpointUrl = $endpointUrl;
        }
    }
    
    /**
     * return a String with all thirdparty Scripts. It is a String
     * with script-tag and src. Src will loaded from config.ini
     *
     * @return string
     */
    public function loadThirdpartyJsFilesFromConfig()
    {
        $files = INIController::fetchSection(Constants::CONFIG_FILE, 'ThirdPartyFiles');
        $scripts = "";
        foreach ($files as $path) {
            $scripts .= "<script src='" . $path . "'></script>";
        }
        
        return $scripts;
    }
    
    /**
     * load all local templates paths which is needed for Widget
     *
     * @param string $filetype
     *
     * @return array
     */
    public function loadFilesFromConfig($filetype)
    {
        $files = INIController::fetchSection(Constants::CONFIG_FILE, $filetype);
        
        return ($files) ? $files : [];
    }
    
    /**
     * load url Path from PlugInt files folder from the general config file
     *
     * @return string
     */
    public function loadPlugIntFilesFolderPathFromConfig()
    {
        $paths = INIController::fetchKey(Constants::CONFIG_FILE, 'PlugIntFilesFolderPath', 'url');
        
        return ($paths) ? $paths : "";
    }
    
    /**
     * load the List of keys from api config file
     *
     * @param string $block
     *
     * @return array
     */
    public function loadMappingArrayFromApiConfig($block)
    {
        $mappingArray = INIController::fetchSection(Constants::CONFIG_APIFILE, $block);
        
        return ($mappingArray) ? $mappingArray : [];
    }
    
    /**
     * Map with the class Mapping the given values to an assoziative array which is needed
     * for form or GET-params in url. Keys from assoziative array will be names for form or GET-parameters
     * in url. Values of the assoziative array will be values for form or GET-parameters in url.
     *
     * @param BusinessLayer\Netpay\Utilities\Shopdata|null $shopData Optional parameter. All Shopdata which is needed for a Request or form
     * @param string $block 
     * 
     * @return array
     */
    public function mapToRequestArray($shopData, $block)
    {
        $map = new Mapping($block);
        ($shopData) ? $map->convertToKeyArray($shopData, 'shopdata') : '';
        $requestArray = $map->convertToRequestArray();
        
        return $requestArray;
    }
    
    /**
     * load all callback urls from congig.ini
     *
     * @param string $block
     *
     * @return array
     */
    public function loadCallbackUrlsFromConfig($block)
    {
        $callbackUrls = INIController::fetchSection(Constants::CONFIG_FILE, $block);
       
        return ($callbackUrls) ? $callbackUrls : [];
    }
    
    /**
     * return which template engine is used in Shopsystem. This is depend on
     * shopsystem name and shopsystem version
     *
     * @param array $shop Required. Have informations about shopname and shopversion
     *
     * @return string
     */
    private function getTemplateEngineByShopsystem($shop)
    {
        switch ($shop['name'] . $shop['version']) {
            case 'Prestashop1.6':
                $type = 'tpl';
                break;
            case 'Prestashop1.7':
                $type = 'tpl';
                break;
            case 'Opencart2':
                $type = 'tpl';
                break;
            case 'Opencart3':
                $type = 'twig';
                break;
            case 'Magento1':
                $type = 'phtml';
                break;
            case 'Magento2':
                $type = 'phtml';
                break;
            case 'Shopware5':
                $type = 'tpl';
                break;
            case 'Shopware6':
                $type = 'twig';
                break;
            case 'Woocommerce452':
                $type = 'php';
                break;
        }
        
        return $type ;
    }
}
