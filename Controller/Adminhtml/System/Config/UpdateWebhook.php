<?php

namespace Netpay\Payment\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Framework\DataObject as DataObject;
use Netpay\Payment\Logger\Logger;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Netpay\Payment\Helper\Data as DataHelper;
use Magento\Framework\Controller\Result\Json;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class UpdateWebhook
 *
 * This is ajax controller
 * It executes and update Webhook URL from api call
 */
class UpdateWebhook extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var DataHelper */
    protected $dataHelper;
    
    /** @var Logger */
    protected $logger;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * updateWebhook constructor
     *
     * @param Context               $context
     * @param JsonFactory           $resultJsonFactory
     * @param ConfigHelper          $configHelper
     * @param DataHelper            $dataHelper
     * @param Logger                $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Resolve which store's NetPay credentials this request must act on.
     *
     * The config form is scope-aware: the admin may be editing Default Config, a website, or a
     * store view, and each scope can hold a different NetPay account. The scope travels in the
     * admin URL (`website=` / `store=`) and the form's JS forwards it here. Without this the
     * credentials always came from the default scope, so registering the webhook while editing a
     * website silently acted on the wrong NetPay account.
     *
     * @return int|null Store id to read credentials from, or null for the default scope.
     */
    private function resolveScopeStoreId()
    {
        $storeParam = $this->getRequest()->getParam('store');
        if (!empty($storeParam)) {
            return (int) $this->storeManager->getStore($storeParam)->getId();
        }

        $websiteParam = $this->getRequest()->getParam('website');
        if (!empty($websiteParam)) {
            // A website has no config of its own to charge against; use its default store, which
            // is what inherits the website-scoped credentials.
            $website = $this->storeManager->getWebsite($websiteParam);
            $defaultStore = $website->getDefaultStore();
            if ($defaultStore && $defaultStore->getId()) {
                return (int) $defaultStore->getId();
            }
        }

        return null;
    }

    /**
     * Human-readable name of the scope being configured, for the message shown to the admin.
     *
     * @return string
     */
    private function getScopeLabel()
    {
        try {
            $storeParam = $this->getRequest()->getParam('store');
            if (!empty($storeParam)) {
                return (string) __('store view "%1"', $this->storeManager->getStore($storeParam)->getName());
            }
            $websiteParam = $this->getRequest()->getParam('website');
            if (!empty($websiteParam)) {
                return (string) __('website "%1"', $this->storeManager->getWebsite($websiteParam)->getName());
            }
        } catch (\Exception $e) {
            $this->logger->debug('Netpay could not resolve the config scope name: ' . $e->getMessage());
        }

        return (string) __('the default scope');
    }

    public function execute()
    {
        $newHookUrl = $this->getRequest()->getParam('webhook');
        $mode = $this->getRequest()->getParam('payment_mode');
        $scopeStoreId = $this->resolveScopeStoreId();
        $scopeLabel = $this->getScopeLabel();
        $others = new DataObject();
        $others->hookUrl = $newHookUrl;
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();
        // Credentials come from the scope being edited, not from the ambient admin store.
        $paymentManager = $this->dataHelper->getPaymentManager($scopeStoreId);

        // Look the current webhook up in its own try: the lookup failing must not prevent the
        // registration below (it used to abort the whole action).
        //
        // Create-vs-update is decided on the presence of the record's **id**, matching NetPay's
        // WooCommerce plugin (`isset($get_webhook["result"]["id"])`). Deciding on the `webhook`
        // value instead would be wrong for a fresh account: per NetPay's docs the record already
        // exists with its id and the webhook field starts as NULL, so that case needs a PUT.
        $recordExists = false;
        try {
            $response = $paymentManager->getWebhook();
            $recordExists = !empty($response->id);
        } catch (\Exception $e) {
            $this->logger->debug('Netpay getWebhook failed, assuming no record exists: ' . $e->getMessage());
        }

        try {
            $paymentManager->setShopdata(null, $others);
            if ($recordExists) {
                $paymentManager->updateWebhook();
            } else {
                $paymentManager->setWebhook();
            }
        } catch(\Exception $e) {
            $this->logger->debug($e->getMessage());
            return $result->setData(
                [
                    'success' => false,
                    'message' => (string) __(
                        'Could not register the webhook in NetPay for %1: %2',
                        $scopeLabel,
                        $e->getMessage()
                    ),
                    'url' => $this->configHelper->getCashWebhook($scopeStoreId)
                ]
            );
        }

        return $result->setData(
            [
                'success' => true,
                // Name the scope explicitly: the same form serves Default / website / store view,
                // and each may hold a different NetPay account.
                'message' => (string) __(
                    'Webhook registered in the NetPay account of %1 (%2 mode). Remember to save the configuration.',
                    $scopeLabel,
                    $mode
                )
            ]
        );
    }
}