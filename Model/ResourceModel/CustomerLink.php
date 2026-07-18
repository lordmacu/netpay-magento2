<?php
namespace Netpay\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Collection
 * execute sql queries.
 */
class CustomerLink extends AbstractDb
{
    const TABLE_NAME = 'netpay_customer';
    
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
    }
}
