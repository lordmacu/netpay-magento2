<?php

namespace Netpay\Payment\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Netpay\Payment\Helper\Data as DataHelper;
use Magento\Framework\Controller\Result\Json;
use Netpay\Payment\Logger\Logger;

/**
 * Class IsCashEnable
 *
 * This is ajax controller
 * It executes and get cash enable or not from api call
 */
class IsCashEnable extends Action
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
     * iscashenable constructor
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
        Logger $logger,
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();
        $storeId = $this->configHelper->getStoreId();
        $isCashEnable  = $this->configHelper->isCashEnabled($storeId);

        if ($isCashEnable == '0') {
            try {
                $paymentManager = $this->dataHelper->getPaymentManager();
                $response = $paymentManager->getStores();
                if ($response->oxxoPayEnable == true) {
                    return $result->setData(
                        [
                            'success' => true,
                            'oxxoPayEnable' => 'Yes'
                        ]
                    );
                } else {
                    return $result->setData(
                        [
                            'success' => false,
                            'message' => 'Oxxopay is not active and can not acivated!!',
                            'url' => $this->configHelper->getCashWebhook($storeId)
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                return $result->setData(
                    [
                        'success' => false,
                        'message' => 'There is some problem on activating OxxoPay!!'
                    ]
                );
            }
        } else {
            return $result->setData(
                [
                    'success' => true,
                    'oxxoPayEnable' => 'No'
                ]
            );
        }   
    }
}