<?php
namespace Netpay\Payment\Model;

use Netpay\Payment\Api\CustomerLinkRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Netpay\Payment\Api\Data\CustomerLinkInterface;
use Netpay\Payment\Api\Data\CustomerLinkSearchResultsInterfaceFactory;
use Netpay\Payment\Model\ResourceModel\CustomerLink\CollectionFactory;
use Netpay\Payment\Model\ResourceModel\CustomerLink as CustomerLinkResourceModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * 
 */
class CustomerLinkRepository implements CustomerLinkRepositoryInterface
{
    /**
     * @var CustomerLinkResourceModel
     */
    private $resourceModel;

    /**
     * @var CustomerLinkFactory
     */
    private $customerLinkFactory;

    /**
     * @var CustomerLinkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CustomerLinkResourceModel $resourceModel
     * @param CustomerLinkFactory $customerLinkFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerLinkSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface | null $collectionProcessor
     */
    public function __construct(
        CustomerLinkResourceModel $resourceModel,
        CustomerLinkFactory $customerLinkFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerLinkSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceModel = $resourceModel;
        $this->customerLinkFactory = $customerLinkFactory;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->storeManager = $storeManager;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($customerId)
    {
        // The NetPay client id belongs to a specific store's NetPay account. Scope the lookup
        // to the current store so that (with per-store accounts) a customer's saved-card client
        // id is never reused across stores/accounts.
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1);
        $item = $collection->getFirstItem();
        if ($item->getId()) {
            return $item;
        }

        return $this->customerLinkFactory->create();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CustomerLinkInterface $customerLink)
    {
        try {
            $this->resourceModel->delete($customerLink);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(CustomerLinkInterface $customerLink)
    {
        try {
            $this->resourceModel->save($customerLink);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not save Netpay customer link: %1', $exception->getMessage()),
                $exception
            );
        }
        return $customerLink;
    }
}
