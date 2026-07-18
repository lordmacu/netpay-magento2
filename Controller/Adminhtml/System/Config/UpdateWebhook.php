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

    /**
     * updateWebhook constructor
     *
     * @param Context         $context
     * @param JsonFactory     $resultJsonFactory
     * @param ConfigHelper    $configHelper
     * @param DataHelper      $dataHelper
     * @param Logger          $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $newHookUrl = $this->getRequest()->getParam('webhook');
        $mode = $this->getRequest()->getParam('payment_mode');
        $storeId = $this->configHelper->getStoreId();
        $others = new DataObject();
        $others->hookUrl = $newHookUrl;
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();
        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $response = $paymentManager->getWebhook();
            $paymentManager->setShopdata(null, $others);
            if ($response->webhook == '') {
                $updateResponse = $paymentManager->setWebhook();
            } else  {
                $updateResponse = $paymentManager->updateWebhook();
            }
        } catch(\Exception $e) {
            $this->logger->debug($e->getMessage());
            return $result->setData(
                [
                    'success' => false,
                    'message' => 'Webhook not found, make sure you have cash option enabled in manager',
                    'url' => $this->configHelper->getCashWebhook($storeId)
                ]
            );
        }
        
        return $result->setData(
            [
                'success' => true,
                'message' => 'Hook for ' . $mode . ' was successful updated. Please save it now.'
            ]
        );
    }
}