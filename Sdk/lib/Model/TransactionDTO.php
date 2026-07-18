<?php
/**
 * TransactionDTO
 *
 * PHP version 5
 *
 * @category Class
 * @package  Netpay\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */

namespace Netpay\Client\Model;

use \ArrayAccess;
use \Netpay\Client\ObjectSerializer;

/**
 * TransactionDTO Class Doc Comment
 *
 * @category Class
 * @package  Netpay\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class TransactionDTO implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $swaggerModelName = 'TransactionDTO';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerTypes = [
        'status'=> 'string',
        'created_at'=> 'string',
        'time_out'=> 'string',
        'order_id'=> 'string',
        'merchant_reference_code'=> 'string',
        'span_route_number'=> 'string',
        'auth_code'=> 'string',
        'bank_name'=> 'string',
        'card_nature'=> 'string',
        'merchant_id'=> 'string',
        'promotion'=> 'float',
        'eci'=> 'float',
        'transaction_token_id'=> 'string',
        'store_name'=> 'string',
        'response_code'=> 'float'
     ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerFormats = [
        'status'=> null,
        'created_at'=> null,
        'time_out'=> null,
        'order_id'=> null,
        'merchant_reference_code'=> null,
        'span_route_number'=> null,
        'auth_code'=> null,
        'bank_name'=> null,
        'card_nature'=> null,
        'merchant_id'=> null,
        'promotion'=> null,
        'eci'=> null,
        'transaction_token_id'=> null,
        'store_name'=> null,
        'response_code'=> null
    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerFormats()
    {
        return self::$swaggerFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'status'=> 'status',
        'created_at'=> 'createdAt',
        'time_out'=> 'timeOut',
        'order_id'=> 'orderId',
        'merchant_reference_code'=> 'merchantReferenceCode',
        'span_route_number'=> 'spanRouteNumber',
        'auth_code'=> 'authCode',
        'bank_name'=> 'bankName',
        'card_nature'=> 'cardNature',
        'merchant_id'=> 'merchantId',
        'promotion'=> 'promotion',
        'eci'=> 'eci',
        'transaction_token_id'=> 'transactionTokenId',
        'store_name'=> 'storeName',
        'response_code'=> 'responseCode'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'status'=> 'setStatus',
        'created_at'=> 'setCreatedAt',
        'time_out'=> 'setTimeOut',
        'order_id'=> 'setOrderId',
        'merchant_reference_code'=> 'setMerchantReferenceCode',
        'span_route_number'=> 'setSpanRouteNumber',
        'auth_code'=> 'setAuthCode',
        'bank_name'=> 'setBankName',
        'card_nature'=> 'setCardNature',
        'merchant_id'=> 'setMerchantId',
        'promotion'=> 'setPromotion',
        'eci'=> 'setEci',
        'transaction_token_id'=> 'setTransactionTokenId',
        'store_name'=> 'setStoreName',
        'response_code'=> 'setResponseCode'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'status'=> 'getStatus',
        'created_at'=> 'getCreatedAt',
        'time_out'=> 'getTimeOut',
        'order_id'=> 'getOrderId',
        'merchant_reference_code'=> 'getMerchantReferenceCode',
        'span_route_number'=> 'getSpanRouteNumber',
        'auth_code'=> 'getAuthCode',
        'bank_name'=> 'getBankName',
        'card_nature'=> 'getCardNature',
        'merchant_id'=> 'getMerchantId',
        'promotion'=> 'getPromotion',
        'eci'=> 'getEci',
        'transaction_token_id'=> 'getTransactionTokenId',
        'store_name'=> 'getStoreName',
        'response_code'=> 'getResponseCode'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$swaggerModelName;
    }

    

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(?array $data = null)
    {
        $this->container['status'] = isset($data['status']) ? $data['status'] : null;
        $this->container['created_at'] = isset($data['created_at']) ? $data['created_at'] : null;
        $this->container['time_out'] = isset($data['time_out']) ? $data['time_out'] : null;
        $this->container['order_id'] = isset($data['order_id']) ? $data['order_id'] : null;
        $this->container['merchant_reference_code'] = isset($data['merchant_reference_code']) ? $data['merchant_reference_code'] : null;
        $this->container['span_route_number'] = isset($data['span_route_number']) ? $data['span_route_number'] : null;
        $this->container['auth_code'] = isset($data['auth_code']) ? $data['auth_code'] : null;
        $this->container['bank_name'] = isset($data['bank_name']) ? $data['bank_name'] : null;
        $this->container['card_nature'] = isset($data['card_nature']) ? $data['card_nature'] : null;
        $this->container['merchant_id'] = isset($data['merchant_id']) ? $data['merchant_id'] : null;
        $this->container['promotion'] = isset($data['promotion']) ? $data['promotion'] : null;
        $this->container['eci'] = isset($data['eci']) ? $data['eci'] : null;
        $this->container['transaction_token_id'] = isset($data['transaction_token_id']) ? $data['transaction_token_id'] : null;
        $this->container['store_name'] = isset($data['store_name']) ? $data['store_name'] : null;
        $this->container['response_code'] = isset($data['response_code']) ? $data['response_code'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->container['status'];
    }

    /**
     * Sets status
     *
     * @param string $status status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->container['status'] = $status;

        return $this;
    }
    /**
     * Gets created_at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->container['created_at'];
    }

    /**
     * Sets created_at
     *
     * @param string $created_at created_at
     *
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->container['created_at'] = $created_at;

        return $this;
    }
    /**
     * Gets time_out
     *
     * @return string
     */
    public function getTimeOut()
    {
        return $this->container['time_out'];
    }

    /**
     * Sets time_out
     *
     * @param string $time_out time_out
     *
     * @return $this
     */
    public function setTimeOut($time_out)
    {
        $this->container['time_out'] = $time_out;

        return $this;
    }
    /**
     * Gets orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->container['orderId'];
    }

    /**
     * Sets orderId
     *
     * @param string $orderId orderId
     *
     * @return $this
     */
    public function setOrderId($orderId)
    {
        $this->container['orderId'] = $orderId;

        return $this;
    }
    /**
     * Gets merchantReferenceCode
     *
     * @return string
     */
    public function getMerchantReferenceCode()
    {
        return $this->container['merchantReferenceCode'];
    }

    /**
     * Sets merchantReferenceCode
     *
     * @param string $merchantReferenceCode merchantReferenceCode
     *
     * @return $this
     */
    public function setMerchantReferenceCode($merchantReferenceCode)
    {
        $this->container['merchantReferenceCode'] = $merchantReferenceCode;

        return $this;
    }
    /**
     * Gets spanRouteNumber
     *
     * @return string
     */
    public function getSpanRouteNumber()
    {
        return $this->container['spanRouteNumber'];
    }

    /**
     * Sets spanRouteNumber
     *
     * @param string $spanRouteNumber spanRouteNumber
     *
     * @return $this
     */
    public function setSpanRouteNumber($spanRouteNumber)
    {
        $this->container['spanRouteNumber'] = $spanRouteNumber;

        return $this;
    }
    /**
     * Gets authCode
     *
     * @return string
     */
    public function getAuthCode()
    {
        return $this->container['authCode'];
    }

    /**
     * Sets authCode
     *
     * @param string $authCode authCode
     *
     * @return $this
     */
    public function setAuthCode($authCode)
    {
        $this->container['authCode'] = $authCode;

        return $this;
    }
    /**
     * Gets bankName
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->container['bankName'];
    }

    /**
     * Sets bankName
     *
     * @param string $bankName bankName
     *
     * @return $this
     */
    public function setBankName($bankName)
    {
        $this->container['bankName'] = $bankName;

        return $this;
    }
    /**
     * Gets cardNature
     *
     * @return string
     */
    public function getCardNature()
    {
        return $this->container['cardNature'];
    }

    /**
     * Sets cardNature
     *
     * @param string $cardNature cardNature
     *
     * @return $this
     */
    public function setCardNature($cardNature)
    {
        $this->container['cardNature'] = $cardNature;

        return $this;
    }
    /**
     * Gets merchantId
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->container['merchantId'];
    }

    /**
     * Sets merchantId
     *
     * @param string $merchantId merchantId
     *
     * @return $this
     */
    public function setMerchantId($merchantId)
    {
        $this->container['merchantId'] = $merchantId;

        return $this;
    }
    /**
     * Gets promotion
     *
     * @return string
     */
    public function getPromotion()
    {
        return $this->container['promotion'];
    }

    /**
     * Sets promotion
     *
     * @param string $promotion promotion
     *
     * @return $this
     */
    public function setPromotion($promotion)
    {
        $this->container['promotion'] = $promotion;

        return $this;
    }
    /**
     * Gets eci
     *
     * @return string
     */
    public function getEci()
    {
        return $this->container['eci'];
    }

    /**
     * Sets eci
     *
     * @param string $eci eci
     *
     * @return $this
     */
    public function setEci($eci)
    {
        $this->container['eci'] = $eci;

        return $this;
    }
    /**
     * Gets transactionTokenId
     *
     * @return string
     */
    public function getTransactionTokenId()
    {
        return $this->container['transactionTokenId'];
    }

    /**
     * Sets transactionTokenId
     *
     * @param string $transactionTokenId transactionTokenId
     *
     * @return $this
     */
    public function setTransactionTokenId($transactionTokenId)
    {
        $this->container['transactionTokenId'] = $transactionTokenId;

        return $this;
    }
    /**
     * Gets storeName
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->container['storeName'];
    }

    /**
     * Sets storeName
     *
     * @param string $storeName storeName
     *
     * @return $this
     */
    public function setStoreName($storeName)
    {
        $this->container['storeName'] = $storeName;

        return $this;
    }
    /**
     * Gets responseCode
     *
     * @return string
     */
    public function getResponseCode()
    {
        return $this->container['responseCode'];
    }

    /**
     * Sets responseCode
     *
     * @param string $responseCode responseCode
     *
     * @return $this
     */
    public function setResponseCode($responseCode)
    {
        $this->container['responseCode'] = $responseCode;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     *
     * @param integer $offset Offset
     * @param mixed   $value  Value to be set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(
                ObjectSerializer::sanitizeForSerialization($this),
                JSON_PRETTY_PRINT
            );
        }

        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}
