<?php

namespace Netpay\Payment\Model\Adminhtml\System\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\UrlInterface;

/**
 * Class DefaultHook
 */
class DefaultHook extends Value
{
    /** @var UrlInterface */
    protected $urlInterface;
    
    /**
     * @param UrlInterface $urlInterface
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        UrlInterface $urlInterface,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->urlInterface = $urlInterface;
    }
    
    /**
     * If it is empty show default string value after loading
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        if (empty($value)) {
            $url = $this->urlInterface->getBaseUrl();
            $this->setValue($url . 'netpay/payment/apicontroller');
        }
        
        return $this;
    }
    
}
