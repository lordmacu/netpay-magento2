<?php
namespace Netpay\Payment\Api\Data;

/**
 * @api
 */
interface CustomerLinkSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Gets collection items.
     *
     * @return \Netpay\Payment\Api\Data\CustomerLinkInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Sets collection items.
     *
     * @param \Netpay\Payment\Api\Data\CustomerLinkInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
