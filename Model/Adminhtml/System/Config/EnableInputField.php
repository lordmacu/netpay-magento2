<?php

namespace Netpay\Payment\Model\Adminhtml\System\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * EnableInputField
 */
class EnableInputField extends Value
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    
    /**
     * Convert to String value after loading
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        ($value == '1') ? $this->setValue('Yes') : $this->setValue('No');
        
        return $this;
    }

    /**
     * convert to boolean value before saving
     *
     * @return void
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();
        if (!empty($value)) {
            ($value == 'Yes') ? $this->setValue('1') : $this->setValue('0');
        }
        
        return $this;
    }
}
