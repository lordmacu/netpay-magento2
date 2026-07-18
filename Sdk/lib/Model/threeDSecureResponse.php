<?php
/**
 * threeDSecureResponse
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
 * threeDSecureResponse Class Doc Comment
 *
 * @category Class
 * @package  Netpay\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class threeDSecureResponse implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $swaggerModelName = 'threeDSecureResponse';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerTypes = [
        'response_code'=> 'string',
        'status'=> 'string',
        'redirect'=> 'string',
        'auth_url'=> 'string',
        'jwt'=> 'string',
        'acs_url'=> 'string',
        'pa_req'=> 'string',
        'api_identifier'=> 'string',
        'api_key'=> 'string',
        'org_unit_id'=> 'string',
        'specification_version'=> 'string',
        'eci'=> 'string',
        'eci_raw'=> 'string',
        'authentication_transaction_id'=> 'string',
        'continue_transaction'=> 'string',
        'friction_less'=> 'string',
        'xid'=> 'string',
        'pares_status'=> 'string'
     ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerFormats = [
        'response_code'=> null,
        'status'=> null,
        'redirect'=> null,
        'auth_url'=> null,
        'jwt'=> null,
        'acs_url'=> null,
        'pa_req'=> null,
        'api_identifier'=> null,
        'api_key'=> null,
        'org_unit_id'=> null,
        'specification_version'=> null,
        'eci'=> null,
        'eci_raw'=> null,
        'authentication_transaction_id'=> null,
        'continue_transaction'=> null,
        'friction_less'=> null,
        'xid'=> null,
        'pares_status'=> null
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
        'response_code'=> 'responseCode',
        'status'=> 'status',
        'redirect'=> 'redirect',
        'auth_url'=> 'authUrl',
        'jwt'=> 'jwt',
        'acs_url'=> 'acsUrl',
        'pa_req'=> 'paReq',
        'api_identifier'=> 'apiIdentifier',
        'api_key'=> 'apiKey',
        'org_unit_id'=> 'orgUnitId',
        'specification_version'=> 'specificationVersion',
        'eci'=> 'eci',
        'eci_raw'=> 'eciRaw',
        'authentication_transaction_id'=> 'authenticationTransactionID',
        'continue_transaction'=> 'continueTransaction',
        'friction_less'=> 'frictionLess',
        'xid'=> 'xid',
        'pares_status'=> 'paresStatus'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'response_code'=> 'setResponseCode',
        'status'=> 'setStatus',
        'redirect'=> 'setRedirect',
        'auth_url'=> 'setAuthUrl',
        'jwt'=> 'setJwt',
        'acs_url'=> 'setAcsUrl',
        'pa_req'=> 'setPaReq',
        'api_identifier'=> 'setApiIdentifier',
        'api_key'=> 'setApiKey',
        'org_unit_id'=> 'setOrgUnitId',
        'specification_version'=> 'setSpecificationVersion',
        'eci'=> 'setEci',
        'eci_raw'=> 'setEciRaw',
        'authentication_transaction_id'=> 'setAuthenticationTransactionID',
        'continue_transaction'=> 'setContinueTransaction',
        'friction_less'=> 'setFrictionLess',
        'xid'=> 'setXid',
        'pares_status'=> 'setParesStatus'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'response_code'=> 'getResponseCode',
        'status'=> 'getStatus',
        'redirect'=> 'getRedirect',
        'auth_url'=> 'getAuthUrl',
        'jwt'=> 'getJwt',
        'acs_url'=> 'getAcsUrl',
        'pa_req'=> 'getPaReq',
        'api_identifier'=> 'getApiIdentifier',
        'api_key'=> 'getApiKey',
        'org_unit_id'=> 'getOrgUnitId',
        'specification_version'=> 'getSpecificationVersion',
        'eci'=> 'getEci',
        'eci_raw'=> 'getEciRaw',
        'authentication_transaction_id'=> 'getAuthenticationTransactionID',
        'continue_transaction'=> 'getContinueTransaction',
        'friction_less'=> 'getFrictionLess',
        'xid'=> 'getXid',
        'pares_status'=> 'getParesStatus'
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
        $this->container['response_code'] = isset($data['response_code']) ? $data['response_code'] : null;
        $this->container['status'] = isset($data['status']) ? $data['status'] : null;
        $this->container['redirect'] = isset($data['redirect']) ? $data['redirect'] : null;
        $this->container['auth_url'] = isset($data['auth_url']) ? $data['auth_url'] : null;
        $this->container['jwt'] = isset($data['jwt']) ? $data['jwt'] : null;
        $this->container['acs_url'] = isset($data['acs_url']) ? $data['acs_url'] : null;
        $this->container['pa_req'] = isset($data['pa_req']) ? $data['pa_req'] : null;
        $this->container['api_identifier'] = isset($data['api_identifier']) ? $data['api_identifier'] : null;
        $this->container['api_key'] = isset($data['api_key']) ? $data['api_key'] : null;
        $this->container['org_unit_id'] = isset($data['org_unit_id']) ? $data['org_unit_id'] : null;
        $this->container['specification_version'] = isset($data['specification_version']) ? $data['specification_version'] : null;
        $this->container['eci'] = isset($data['eci']) ? $data['eci'] : null;
        $this->container['eci_raw'] = isset($data['eci_raw']) ? $data['eci_raw'] : null;
        $this->container['authentication_transaction_id'] = isset($data['authentication_transaction_id']) ? $data['authentication_transaction_id'] : null;
        $this->container['continue_transaction'] = isset($data['continue_transaction']) ? $data['continue_transaction'] : null;
        $this->container['friction_less'] = isset($data['friction_less']) ? $data['friction_less'] : null;
        $this->container['xid'] = isset($data['xid']) ? $data['xid'] : null;
        $this->container['pares_status'] = isset($data['pares_status']) ? $data['pares_status'] : null;
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
     * Gets response_code
     *
     * @return string
     */
    public function getResponseCode()
    {
        return $this->container['response_code'];
    }

    /**
     * Sets response_code
     *
     * @param string $response_code response_code
     *
     * @return $this
     */
    public function setResponseCode($response_code)
    {
        $this->container['response_code'] = $response_code;

        return $this;
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
     * Gets redirect
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->container['redirect'];
    }

    /**
     * Sets redirect
     *
     * @param string $redirect redirect
     *
     * @return $this
     */
    public function setRedirect($redirect)
    {
        $this->container['redirect'] = $redirect;

        return $this;
    }
    /**
     * Gets auth_url
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->container['auth_url'];
    }

    /**
     * Sets auth_url
     *
     * @param string $auth_url auth_url
     *
     * @return $this
     */
    public function setAuthUrl($auth_url)
    {
        $this->container['auth_url'] = $auth_url;

        return $this;
    }
    /**
     * Gets jwt
     *
     * @return string
     */
    public function getJwt()
    {
        return $this->container['jwt'];
    }

    /**
     * Sets jwt
     *
     * @param string $jwt jwt
     *
     * @return $this
     */
    public function setJwt($jwt)
    {
        $this->container['jwt'] = $jwt;

        return $this;
    }
    /**
     * Gets acs_url
     *
     * @return string
     */
    public function getAcsUrl()
    {
        return $this->container['acs_url'];
    }

    /**
     * Sets acs_url
     *
     * @param string $acs_url acs_url
     *
     * @return $this
     */
    public function setAcsUrl($acs_url)
    {
        $this->container['acs_url'] = $acs_url;

        return $this;
    }
    /**
     * Gets pa_req
     *
     * @return string
     */
    public function getPaReq()
    {
        return $this->container['pa_req'];
    }

    /**
     * Sets pa_req
     *
     * @param string $pa_req pa_req
     *
     * @return $this
     */
    public function setPaReq($pa_req)
    {
        $this->container['pa_req'] = $pa_req;

        return $this;
    }
    /**
     * Gets api_identifier
     *
     * @return string
     */
    public function getApiIdentifier()
    {
        return $this->container['api_identifier'];
    }

    /**
     * Sets api_identifier
     *
     * @param string $api_identifier api_identifier
     *
     * @return $this
     */
    public function setApiIdentifier($api_identifier)
    {
        $this->container['api_identifier'] = $api_identifier;

        return $this;
    }
    /**
     * Gets api_key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->container['api_key'];
    }

    /**
     * Sets api_key
     *
     * @param string $api_key api_key
     *
     * @return $this
     */
    public function setApiKey($api_key)
    {
        $this->container['api_key'] = $api_key;

        return $this;
    }
    /**
     * Gets org_unit_id
     *
     * @return string
     */
    public function getOrgUnitId()
    {
        return $this->container['org_unit_id'];
    }

    /**
     * Sets org_unit_id
     *
     * @param string $org_unit_id org_unit_id
     *
     * @return $this
     */
    public function setOrgUnitId($org_unit_id)
    {
        $this->container['org_unit_id'] = $org_unit_id;

        return $this;
    }
    /**
     * Gets specification_version
     *
     * @return string
     */
    public function getSpecificationVersion()
    {
        return $this->container['specification_version'];
    }

    /**
     * Sets specification_version
     *
     * @param string $specification_version specification_version
     *
     * @return $this
     */
    public function setSpecificationVersion($specification_version)
    {
        $this->container['specification_version'] = $specification_version;

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
     * Gets eci_raw
     *
     * @return string
     */
    public function getEciRaw()
    {
        return $this->container['eci_raw'];
    }

    /**
     * Sets eci_raw
     *
     * @param string $eci_raw eci_raw
     *
     * @return $this
     */
    public function setEciRaw($eci_raw)
    {
        $this->container['eci_raw'] = $eci_raw;

        return $this;
    }
    /**
     * Gets authentication_transaction_id
     *
     * @return string
     */
    public function getAuthenticationTransactionID()
    {
        return $this->container['authentication_transaction_id'];
    }

    /**
     * Sets authentication_transaction_id
     *
     * @param string $authentication_transaction_id authentication_transaction_id
     *
     * @return $this
     */
    public function setAuthenticationTransactionID($authentication_transaction_id)
    {
        $this->container['authentication_transaction_id'] = $authentication_transaction_id;

        return $this;
    }
    /**
     * Gets continue_transaction
     *
     * @return string
     */
    public function getContinueTransaction()
    {
        return $this->container['continue_transaction'];
    }

    /**
     * Sets continue_transaction
     *
     * @param string $continue_transaction continue_transaction
     *
     * @return $this
     */
    public function setContinueTransaction($continue_transaction)
    {
        $this->container['continue_transaction'] = $continue_transaction;

        return $this;
    }
    /**
     * Gets friction_less
     *
     * @return string
     */
    public function getFrictionLess()
    {
        return $this->container['friction_less'];
    }

    /**
     * Sets friction_less
     *
     * @param string $friction_less friction_less
     *
     * @return $this
     */
    public function setFrictionLess($friction_less)
    {
        $this->container['friction_less'] = $friction_less;

        return $this;
    }
    /**
     * Gets xid
     *
     * @return string
     */
    public function getXid()
    {
        return $this->container['xid'];
    }

    /**
     * Sets xid
     *
     * @param string $xid xid
     *
     * @return $this
     */
    public function setXid($xid)
    {
        $this->container['xid'] = $xid;

        return $this;
    }
    /**
     * Gets pares_status
     *
     * @return string
     */
    public function getParesStatus()
    {
        return $this->container['pares_status'];
    }

    /**
     * Sets pares_status
     *
     * @param string $pares_status pares_status
     *
     * @return $this
     */
    public function setParesStatus($pares_status)
    {
        $this->container['pares_status'] = $pares_status;

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
