<?php
namespace Netpay\Payment\Model;

use Netpay\Payment\Model\ResourceModel\CustomerLink as CustomerLinkResourceModel;
use Magento\Framework\Model\AbstractModel;
use Netpay\Payment\Api\Data\CustomerLinkInterface;

/**
 * Class CustomerLink
 */
class CustomerLink extends AbstractModel implements CustomerLinkInterface
{
    /**
     * Initialize Custom Link resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CustomerLinkResourceModel::class);
    }
    
    /**
     * Set netpay id
     *
     * @param  string $netpayId
     * @return $this
     */
    public function setNetpayId($netpayId)
    {
        return $this->setData('netpay_id', $netpayId);
    }

    /**
     * Get netpay id
     *
     * @return string
     */
    public function getNetpayId()
    {
        return $this->getData('netpay_id');
    }

    /**
     * Set customer id
     *
     * @param  integer $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Get customer id
     *
     * @return integer
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Set store id
     *
     * @param integer $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Get store id
     *
     * @return integer
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }
}
