<?php

/**
 * Constants
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

class Constants
{
    const CONFIG_FILE = __DIR__ . '/../../config/config.ini';
    const CONFIG_APIFILE = __DIR__ . '/../../config/mappingapi.ini';
    const CONFIG_SHOPFILE = __DIR__ . '/../../config/mappingshop.ini';
    const CONFIG_MAPFILE = __DIR__ . '/../../config/mappingvalues.ini';
    const SPLITTING_CHARACTER = ',';
    const KEYWORD_FOR_ARRAY = 'iter';
    const CHECKSUM_ATTRIBUTE_NAME = 'authString';
    const CHECKSUM_VALUE_CUSTOM_FUNCTION = 'customfunction';
    const CURRENTDATE_ATTRIBUTE_NAME = 'currentTimestamp';
    const CUSTOM_FUNCTION_ATTRIBUTE_NAME = 'customAttribute';
    const ENDPOINT_ATTRIBUTE_NAME = 'endpointUrl';
}
