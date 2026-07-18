<?php
namespace Netpay\Payment\Api\Data;

/**
 * @api
 */
interface CustomerLinkInterface
{
    /*
     * Entity ID.
     */
    const ENTITY_ID = 'entity_id';

    /*
     * Customer ID.
     */
    const CUSTOMER_ID = 'customer_id';

    /*
     * Netpay ID.
     */
    const NETPAY_ID = 'netpay_id';

    /*
     * Store ID.
     */
    const STORE_ID = 'store_id';

    /**
     * Set netpay id
     *
     * @param string $netpayId
     *
     * @return $this
     */
    public function setNetpayId($netpayId);

    /**
     * Get netpay id
     *
     * @return string
     */
    public function getNetpayId();

    /**
     * Set customer id
     *
     * @param integer $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get customer id
     *
     * @return integer
     */
    public function getCustomerId();

    /**
     * Set store id
     *
     * @param integer $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get store id
     *
     * @return integer
     */
    public function getStoreId();
}
