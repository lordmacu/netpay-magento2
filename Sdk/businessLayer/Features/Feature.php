<?php

/*
 * Generall functions which can used in all Features
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Features;

use \Netpay\Client\Configuration;
use BusinessLayer\Netpay\Utilities\Mapping;
use GuzzleHttp\Client;
use ReflectionClass;
use BusinessLayer\Netpay\Utilities\INIController;
use BusinessLayer\Netpay\Utilities\Constants;
use BusinessLayer\Netpay\CustomLayer\Customizations;
use Exception;

class Feature
{

    /** @var string */
    private $section;
    /** @var array */
    private $configParams;
    /** @var array */
    private $requestArray = [];
    /** @var object */
    private $apiInstance;
    /** @var string */
    private $debugFile;

    /**
     * Prepare Request (create Feature object). ConfigParams are feature/section parameters
     * from config.ini (e.g. Tokenitation or Refund) or can come from backoffice form
     * from shopsystem. Set ApiInstance, create Request with header and body informations 
     * and create debug file
     *
     * @param string $section
     * @param array|null $configParams
     * @param \BusinessLayer\Netpay\PaymentManager $paymentManager
     */
    public function __construct($section, $configParams, $paymentManager)
    {
        $this->setSection($section);
        if ($configParams == null) {
            $configParams = $this->loadFeatureParamsFromConfig($section);
        }
        $this->setConfigParams($configParams);
        $debugFile = $this->loadDebugPathFromConfig();
        $this->setDebugFile($debugFile);
        $requestArray = $this->mapShopObjToRequestArray($paymentManager->getShopdata(), 'shopdata');
        $this->setRequestArray($requestArray);
        $this->createApiInstance($this->configParams['apiClass'], $paymentManager);
    }
    
    /**
     * Map with the class Mapping the shop Object to an assoziative array.
     * This array will have the structure of the request body
     *
     * @param object|array|null $shopObj
     * @param string $objName
     *
     * @return array
     */
    public function mapShopObjToRequestArray($shopObj, $objName)
    {
        $map = new Mapping($this->section);
        isset($shopObj) ? $map->convertToKeyArray($shopObj, $objName) : '';
        $requestArray = $map->convertToRequestArray();
        
        return $requestArray;
    }
    
    /**
     * create ApiInstance for request call
     * returned value is a instance of a class with namespace Netpay\Client\Api
     *
     * @param string $apiClassname
     * @param \BusinessLayer\Netpay\PaymentManager $paymentManager
     */
    public function createApiInstance($apiClassname, $paymentManager)
    {
        $class = new ReflectionClass($apiClassname);
        $config = $this->createApiConfigInstance($paymentManager->getAuthParams(), $paymentManager->getApiHost());
        $arguments = [
            // Verify the gateway's TLS certificate. NetPay's live and sandbox hosts
            // (suite.netpay.com.mx / gateway-154.netpaydev.com) present publicly-trusted
            // certificates, so there is no reason to disable verification — leaving it off
            // exposes the API key and card token to a man-in-the-middle.
            new Client(array('verify' => true)),
            $config
        ];
        
        $this->apiInstance = $class->newInstanceArgs($arguments);
    }
    
    /**
     * call the API function and send the request
     * write requestbody and responsebody in a logfile if it is defined in config.ini
     *
     * @param array $urlids
     */
    public function sendRequest($urlids = [])
    {
        $attributes = $urlids;

        $body = [];
        try {
            if (isset($this->configParams['requestModel'])) {
                $class = new ReflectionClass($this->configParams['requestModel']);
                $body = $class->newInstanceArgs([$this->getRequestArray()]);
            }
            if (!empty($body)) {
                $attributes[] = $body;
            }

            ($this->getDebugFile()) ? $this->writeLog($body, 'Request') : '';
            if (empty($attributes)) {
                $responseObj = call_user_func([$this->getApiInstance(), $this->configParams['method']]);
            } else {
                $responseObj = call_user_func_array([$this->getApiInstance(), $this->configParams['method']], $attributes);
            }
            ($this->getDebugFile()) ? $this->writeLog($responseObj, 'Response') : '';
            

            return $responseObj;
        } catch (\Exception $e) {
            throw new Exception('Exception when calling ' . $this->configParams['apiClass'] . '->' . $this->configParams['method'] . ': ' . $e->getMessage());
        }
    }
    
    /**
     * create API Config Instance, set the header attribute Authorization and
     * set Hostname if this is coming from Backoffice of Shopsystem 
     *
     * @param array $authParams    Is the assoziative array with the informations for Authorization
     *                             Is coming from Shopsystem or config.ini File
     * @param string|null $apiHost Hostname is different in Test- and Livemachine. If it is
     *                             coming from Backoffice of Shopsystem it can be changed. Default
     *                             is the host which is defined in yaml file
     * 
     * @return \Netpay\Client\Configuration
     */
    private function createApiConfigInstance($authParams, $apiHost)
    {
        $config = Configuration::getDefaultConfiguration();
        switch ($authParams['type']) {
            case 'Basic':
                $config->setUsername($authParams['user']);
                $config->setPassword($authParams['password']);
                break;
            case 'Bearer':
                $config->setApiKey('Authorization', $authParams['password']);
                $config->setApiKeyPrefix('Authorization', 'Bearer');
                break;
            case 'Signature':
                isset($authParams['prefix']) ? $config->setApiKeyPrefix('Authorization', $authParams['prefix']) : '';
                $signatue = Customizations::customSignatureFunction($authParams, $this->requestArray, $this->section);
                $config->setApiKey('Authorization', $signatue);
                break;
        }
        if ($apiHost !== null) {
            $config->setHost($apiHost);
        }
            
        return $config;
    }
    
    /**
     * load configuration params for the feature from the general config file
     *
     * @param string $section Is the name of the Feature (e.g. 'Tokenization', 'Refund')
     */
    private function loadFeatureParamsFromConfig($section)
    {
        return INIController::fetchSection(Constants::CONFIG_FILE, $section);
    }
    
    /**
     * load configuration params for the feature from the general config file
     */
    private function loadDebugPathFromConfig()
    {
        return INIController::fetchKey(Constants::CONFIG_FILE, 'DebugFile', 'path');
    }
    
    /**
     * write the request/response which is sended/received for/from API call
     *
     * @param object|array $object will be the requestbody or the responsebody
     * @param string $type Can be only 'Request' or 'Response'
     */
    public function writeLog($object, $type)
    {
        if (empty($object)) {
            $object = '';
        }
        $msg = date("Y-m-d H:i:s") . "\n";
        $msg .= '********' . $this->section . $type . '********' . "\n";
        $msg .= $object . "\n";
        $msg .= '********************************' . "\n";
        file_put_contents($this->debugFile, $msg, FILE_APPEND);
    }
    
    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }
    
    /**
     * @param sring $section
     */
    public function setSection($section)
    {
        $this->section = $section;
    }

    /**
     * @return array
     */
    public function getRequestArray()
    {
        return $this->requestArray;
    }
    
    /**
     * @param array $requestArray
     */
    public function setRequestArray($requestArray)
    {
        $this->requestArray = $requestArray;
    }

    /**
     * get an ApiInstance. ApiInstance can be every instance with namespace Netpay\Client\Api
     *
     * @return object
     */
    public function getApiInstance()
    {
        return $this->apiInstance;
    }

    /**
     * set an ApiInstance. ApiInstance can be every instance with namespace Netpay\Client\Api
     *
     * @param object $apiInstance
     */
    public function setApiInstance($apiInstance)
    {
        $this->apiInstance = $apiInstance;
    }

    /**
     * @return array
     */
    public function getConfigParams()
    {
        return $this->configParams;
    }
    
    /**
     * @param array $configParams
     */
    public function setConfigParams($configParams)
    {
        $this->configParams = $configParams;
    }
    
    /**
     * @return string
     */
    public function getDebugFile()
    {
        return $this->debugFile;
    }

    /**
     * @param string $debugFile
     */
    public function setDebugFile($debugFile)
    {
        $this->debugFile = $debugFile;
    }
}
