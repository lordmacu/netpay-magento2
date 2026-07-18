<?php

/*
 * Units for dictionary
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

class DicUnit
{

    /** @var string */
    public $shopkey;
    
    /** @var mixed */
    public $value;
    
    /** @var array */
    public $generalconfkeys;
    
    /** @var array */
    public $iters;
    
    /**
     *
     * @param string $shopkey
     * @param mixed $value
     * @param array $generalconfkeys
     * @param array $iters
     */
    public function __construct($shopkey, $value, $generalconfkeys, $iters)
    {
        $this->shopkey = $shopkey;
        $this->value = $value;
        $this->generalconfkeys = $generalconfkeys;
        $this->iters = $iters;
    }
    
    public function getShopkey()
    {
        return $this->shopkey;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getGeneralconfkeys()
    {
        return $this->generalconfkeys;
    }

    public function getIters()
    {
        return $this->iters;
    }

    public function setShopkey($shopkey)
    {
        $this->shopkey = $shopkey;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function setGeneralconfkeys($generalconfkeys)
    {
        $this->generalconfkeys = $generalconfkeys;
    }

    public function setIters($iters)
    {
        $this->iters = $iters;
    }
}
