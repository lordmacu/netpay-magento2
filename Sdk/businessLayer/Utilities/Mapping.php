<?php

/**
 * Mapping
 * This class have the methods to convert the shopobject to
 * the request array, which we will need in the API. For the converting
 * the files mappingapi.ini, mappingshop.ini and mappingvalues will used.
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

use BusinessLayer\Netpay\Utilities\INIController;
use BusinessLayer\Netpay\Utilities\DicUnit;
use BusinessLayer\Netpay\Utilities\Constants;
use BusinessLayer\Netpay\CustomLayer\Customizations;

class Mapping
{
    
    /** @var DicUnit[] */
    public $dic = [];
    /** @var array */
    public $shopkeys;
    /** @var array */
    public $apikeys;
    /** @var array */
    public $mappingValues;
    /** @var string */
    public $section;
    
    public function __construct($section)
    {
        $this->setSection($section);
        $this->loadKeysFromApi($section);
        $this->loadKeysFromShop($section);
        $this->loadValueMapping();
    }

    /**
     * build the array dictionary dic with DicUnit values for preparing mapping
     *
     * @param mixed $shopObject
     * @param string $objectname
     *
     * @return $this
     */
    public function convertToKeyArray($shopObject, $objectname)
    {
        $keyList = [];
        $shopArray = json_decode(json_encode($shopObject), true);
        $this->traverseShopArray($shopArray, $keyList, $objectname);
        
        return $this;
    }
    
    /**
     * load the List of keys from api config file
     *
     * @param string $block
     */
    private function loadKeysFromApi($block)
    {
        $apikeys = INIController::fetchSection(Constants::CONFIG_APIFILE, $block);
        $this->apikeys = (!empty($apikeys)) ? $apikeys : [];
    }
    
    /**
     * load the List of keys from shop config file
     *
     * @param string $block
     */
    private function loadKeysFromShop($block)
    {
        $shopkeys = INIController::fetchSection(Constants::CONFIG_SHOPFILE, $block);
        $this->shopkeys = (!empty($shopkeys)) ? $shopkeys : [];
    }
    
    /**
     * load all values from mappingValue config file
     */
    public function loadValueMapping()
    {
        $this->mappingValues = INIController::fetchFile(Constants::CONFIG_MAPFILE);
    }
    
    /**
     * set Section
     *
     * @param string $block
     */
    public function setSection($block)
    {
        $this->section = $block;
    }
    
    /**
     * convert a shop object like cart to a list of Dicunits for preparing mapping.
     * Recursive function
     *
     * @param array $shopArray
     * @param array $keyList
     * @param string $objectname
     *
     * @return $this
     */
    private function traverseShopArray($shopArray, &$keyList, $objectname)
    {
        if (is_array($shopArray)) {
            $keys = array_keys($shopArray);
            foreach ($keys as $key) {
                array_push($keyList, $key);
                $this->traverseShopArray($shopArray[$key], $keyList, $objectname);
                array_pop($keyList);
            }
            //@Todo:should be elseif and check if string, boolean, number, null
        } else {
            $keyListWithWordIter = $keyList;
            $i = 1;
            $iters = null;
            foreach ($keyListWithWordIter as $index => $key) {
                if (is_numeric($key)) {
                    $keyListWithWordIter[$index] = Constants::KEYWORD_FOR_ARRAY;
                    $iters[Constants::KEYWORD_FOR_ARRAY . $i] = $key;
                    $i++;
                }
            }
            $iterkey = $objectname . Constants::SPLITTING_CHARACTER . implode(Constants::SPLITTING_CHARACTER, $keyListWithWordIter);
            
            if (in_array($iterkey, $this->shopkeys)) {
                $key = $objectname . Constants::SPLITTING_CHARACTER . implode(Constants::SPLITTING_CHARACTER, $keyList);
                $value = $this->changeValue($iterkey, $shopArray);
                $unit = new DicUnit($key, $value, array_keys($this->shopkeys, $iterkey, true), $iters);
                $this->insertInDic($unit);
            }
            
            return $this;
        }
    }
    
    /**
     * Mapping. convert dic to requestArray with help of api config file
     *
     * @return array requestArray
     */
    public function convertToRequestArray()
    {
        $this->expandDicWithSpecialAttributes();
        $requestArray = array();        
        $apiKeysWithSameValue = array();
        foreach ($this->dic as $dicUnit) {
            //$dicUnit->generalconfkeys can have more keys and can be an array            
            foreach ($dicUnit->generalconfkeys as $generalconfkey) {
                if (isset($this->apikeys[$generalconfkey])) {
                    $apikeyString = $this->apikeys[$generalconfkey];
                    $apikeysplit = explode(Constants::SPLITTING_CHARACTER, $apikeyString);
                    $element = $dicUnit->value;
                    foreach (array_reverse($apikeysplit) as $apikey) {
                        $withoutLastChar = substr($apikey, 0, -1);
                        if ($withoutLastChar == Constants::KEYWORD_FOR_ARRAY) {
                            if (isset($dicUnit->iters[$apikey])) {
                                $apikey = $dicUnit->iters[$apikey];
                            } else {
                                $apikey = 0;
                                $apiKeysWithSameValue = array_merge($apiKeysWithSameValue, array($apikeyString=>$dicUnit->value));
                            }
                        }
                        $element = array($apikey => $element);
                    }
                    $requestArray = $this->arrayMergeRecursiveDistinct($requestArray,$element);
                }
            }        
        }
        foreach ($apiKeysWithSameValue as $keywithSameValue=>$value) {
            $apikeysplit = explode(Constants::SPLITTING_CHARACTER, $keywithSameValue);
            $countOfIters = substr_count($keywithSameValue, Constants::KEYWORD_FOR_ARRAY);
            /*Solution for e.g. key items,iter1,name*/
            $wordForArray = Constants::KEYWORD_FOR_ARRAY;
            $matches = array_filter($apikeysplit, function($var) use ($wordForArray) { return preg_match("/$wordForArray/i", $var); });
            $arraydimension = key($matches);
            $insertList = false;
            
            if ($countOfIters === 1) {
                $partialArray = $requestArray;
                for ($i=0; $i<$arraydimension; $i++) {
                    $partialArray = $partialArray[$apikeysplit[$i]];
                    $countElements = count($partialArray);
                }
                $multidimesionalArray = $value;
                foreach (array_reverse($apikeysplit) as $apikey) {
                    $withoutLastChar = substr($apikey, 0, -1);
                    if ($withoutLastChar == Constants::KEYWORD_FOR_ARRAY) {
                        $list = array();
                        for ($i=0; $i<$countElements; $i++) {
                            $list[] = $multidimesionalArray;
                        }
                        $multidimesionalArray = array($apikey => $list);
                        $insertList = true;
                    } else {
                        $multidimesionalArray = ($insertList) ? array($apikey => $list) : array($apikey => $multidimesionalArray);
                        $insertList = false;
                    }
                }
            } else {
                /*Solution for e.g. key items,iter1,name,iter2*/
                /*top Solution should work for $countOfIters > 1 too. But it isn't tested*/
            }
            $requestArray = $this->arrayMergeRecursiveDistinct($requestArray,$multidimesionalArray);
        }
        
        if (isset($this->shopkeys[Constants::CHECKSUM_ATTRIBUTE_NAME]) && 
                $this->mappingValues[$this->section][Constants::CHECKSUM_ATTRIBUTE_NAME] == Constants::CHECKSUM_VALUE_CUSTOM_FUNCTION) {
            $requestArray = Customizations::customBodySignatureFunction($requestArray, $this->dic, $this->shopkeys[Constants::CHECKSUM_ATTRIBUTE_NAME]);
        }
        
        return $requestArray;
    }
    
    /**
     * push new Unit in the array dictionary
     *
     * @param BusinessLayer\Netpay\Utilities\DicUnit $unit
     */
    public function insertInDic($unit)
    {
        array_push($this->dic, $unit);
    }
    
    /**
     * return the converted value with help of mappingvalues.ini
     *
     * @param string $key
     * @param string $shopvalue
     *
     * @return string
     */
    private function changeValue($key, $shopvalue)
    {
        if (!empty($this->mappingValues) && !empty($this->mappingValues[$this->section])) {
            $mappingValuesFromSection = $this->mappingValues[$this->section];
            $mappingkeys = array_keys($mappingValuesFromSection);
            if (in_array($key, $mappingkeys)) {
                $function = $mappingValuesFromSection[$key];
                //@Todo check if method exist
                return (strpos($function, 'map') === false) ? $this->{$function}($shopvalue) : $this->map($shopvalue, $function);
            }
        }
        
        return $shopvalue;
    }
    
    /**
     * return the value/id which request will understand
     *
     * @param string $shopvalue
     * @param string $function
     *
     * @return string
     */
    private function map($shopvalue, $function)
    {
        if (isset($this->mappingValues[$function]) && isset($this->mappingValues[$function][$shopvalue])) {
            $newValue = $this->mappingValues[$function][$shopvalue];
        } else {
            $newValue = $shopvalue;
        }
        
        return $newValue;
    }
    
    /**
     * round e.g. the amount
     *
     * @param string $value
     * @return string
     */
    private function round($value)
    {
        return (string)(round(floatval($value), 2));
    }
    
    /**
     * return the hash encoded $combinedRequestValues
     *
     * @param string $hashfunction
     * @param string $combinedRequestValues
     */
    private function checksum($hashfunction, $combinedRequestValues)
    {
        return hash($hashfunction, $combinedRequestValues);
    }
    
    /**
     * check in mapping.ini if there are some special attributes, e.g authString
     * or currentTimestamp.
     * - authString means that we need a encoded hashed value of some request parameters in
     * the request array. In mappingvalues.ini is the Hashfunction defined
     * - currentTimestamp means that we need a current timestamp in the request array.
     * The value in mappingshop.ini will be the format of the date
     * - customAttribute means that there is a additional attribute. The value in
     * mappingshop.ini is the function in Customization.php
     */
    private function expandDicWithSpecialAttributes()
    {
        if (isset($this->shopkeys[Constants::CURRENTDATE_ATTRIBUTE_NAME])) {
            $currentdate = date($this->shopkeys[Constants::CURRENTDATE_ATTRIBUTE_NAME]);
            $unit = new DicUnit($this->shopkeys[Constants::CURRENTDATE_ATTRIBUTE_NAME], $currentdate, [Constants::CURRENTDATE_ATTRIBUTE_NAME], null);
            $this->insertInDic($unit);
        }
        if (isset($this->shopkeys[Constants::CHECKSUM_ATTRIBUTE_NAME]) && 
                $this->mappingValues[$this->section][Constants::CHECKSUM_ATTRIBUTE_NAME] != Constants::CHECKSUM_VALUE_CUSTOM_FUNCTION) {
            $chechsumAttributes = explode('+', $this->shopkeys[Constants::CHECKSUM_ATTRIBUTE_NAME]);
            $combinedValues = '';
            foreach ($chechsumAttributes as $attribute) {
                $key = array_search($attribute, array_column($this->dic, 'shopkey'));
                $combinedValues .= $this->dic[$key]->value;
            }
            $hashfunction = $this->mappingValues[$this->section][Constants::CHECKSUM_ATTRIBUTE_NAME];
            $checksum = $this->checksum($hashfunction, $combinedValues);
            $unit = new DicUnit($this->shopkeys[Constants::CHECKSUM_ATTRIBUTE_NAME], $checksum, [Constants::CHECKSUM_ATTRIBUTE_NAME], null);
            $this->insertInDic($unit);
        }
        if (isset($this->shopkeys[Constants::CUSTOM_FUNCTION_ATTRIBUTE_NAME])) {
            $dicValue = call_user_func_array(['BusinessLayer\Netpay\CustomLayer\Customizations', $this->shopkeys[Constants::CUSTOM_FUNCTION_ATTRIBUTE_NAME]], array($this->dic));
            $unit = new DicUnit($this->shopkeys[Constants::CUSTOM_FUNCTION_ATTRIBUTE_NAME], $dicValue, [Constants::CUSTOM_FUNCTION_ATTRIBUTE_NAME], null);
            $this->insertInDic($unit);
        }
    }
    
    /**
     * pass arguments (arrays) to this functions which have to merged. This function
     * expand the php function array_merge_recursive. This php function gets problems
     * when we have keys from type integer. With this function it will work.
     * 
     * @return array
     */
    function arrayMergeRecursiveDistinct()
    { 
        $arrays = func_get_args();
        $base = array_shift($arrays);
        if(!is_array($base)) $base = empty($base) ? array() : array($base);
        foreach($arrays as $append) {
            if(!is_array($append)) $append = array($append);
            foreach($append as $key => $value) {
                if(!array_key_exists($key, $base) && !is_numeric($key)) {
                    $base[$key] = $append[$key];
                    continue;
                }
                if(is_array($value) || (array_key_exists($key, $base) && is_array($base[$key]))) {
                    $base[$key] = !isset($base[$key]) ? NULL : $base[$key];
                    $base[$key] = $this->arrayMergeRecursiveDistinct($base[$key], $append[$key]);
                }
                else if(is_numeric($key))
                {
                    if(!in_array($value, $base)) $base[] = $value;
                }
                else {
                    $base[$key] = $value;
                }
            }
        }
        
        return $base;
    }
    
}
