<?php
namespace Netpay\Payment\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 */
interface CustomerLinkRepositoryInterface
{
    /**
     * Lists clients that match specified search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \Netpay\Payment\Api\Data\CustomerLinkSearchResultsInterface search result interface.
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Loads by Customer ID.
     *
     * @param int $customerId The customer ID.
     * @return \Netpay\Payment\Api\Data\CustomerLinkInterface Customer link interface.
     */
    public function get($customerId);

    /**
     * Delete customer link.
     *
     * @param \Netpay\Payment\Api\Data\CustomerLinkInterface Customer link interface.
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CustomerLinkInterface $customerLink);

    /**
     * Save customer link
     *
     * @param \Netpay\Payment\Api\Data\CustomerLinkInterface Customer link interface.
     * @return \Netpay\Payment\Api\Data\CustomerLinkInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\CustomerLinkInterface $customerLink);
}
