<?php
namespace Netpay\Payment\Model\ResourceModel\CustomerLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Netpay\Payment\Model\CustomerLink as CustomerLinkModel;
use Netpay\Payment\Model\ResourceModel\CustomerLink as CustomerLinkResourceModel;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(CustomerLinkModel::class, CustomerLinkResourceModel::class);
    }
}