<?php

namespace Netpay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Netpay\Payment\Model\Netpay;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Netpay\Payment\Logger\Logger;
use Netpay\Payment\Model\CustomerLinkRepository;

/**
 * Class customconfigprovider
 *
 * This class retreive all the admin configuration settings
 * We will use these values on frontend checkout page
 */
class CustomConfigProvider implements ConfigProviderInterface
{
    /** @var string */
    const METHOD_CODE1 = 'netpay';

    const METHOD_CODE2 = 'netpaycash';
     
    /** @var ConfigHelper */
    protected $configHelper;
    
    /** @var DataHelper */
    protected $dataHelper;

    /** @var Netpay */
    protected $netpay;

    /** @var Logger */
    protected $logger;

    /** @var CustomerLinkRepository */
    private $customerLinkRepository;

    protected $customerRepositoryFactory;
    protected $paymentTokenManagement;
    
    /**
     * customconfigprovider constructor
     *
     * @param ConfigHelper $configHelper
     * @param Netpay      $netpay
     */
    public function __construct(
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        Netpay $netpay,
        PaymentTokenManagementInterface $paymentTokenManagement,
        CustomerLinkRepository $customerLinkRepository,
        Logger $logger
    ) {
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->netpay = $netpay;
        $this->customerLinkRepository = $customerLinkRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->logger = $logger;
    }

    /**
     * Set the payment action to authorize_and_capture
     *
     * @return string
     */
    public function getConfig()
    { 
        $creditcards = [];
        $cardNumbers = [];
        $customerId = 0;
        if ($this->dataHelper->getQuote()->getCustomerId()) {
            $customerId = $this->dataHelper->getQuote()->getCustomerId();
            $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);
            $customerLink = $this->customerLinkRepository->get($customerId);
            $clientId = null;
            $getClient = null;
            if (!empty($customerLink->getNetpayId())) {
                // Guard the gateway call: a NetPay API hiccup must not break the checkout config.
                try {
                    $clientId = $customerLink->getNetpayId();
                    $paymentManager = $this->dataHelper->getPaymentManager();
                    $paymentManager->setUrlAttributes([$clientId]);
                    $getClient = $paymentManager->getClient();
                } catch (\Exception $e) {
                    $this->logger->debug('NetPay getClient failed: ' . $e->getMessage());
                    $clientId = null;
                }
            }

            foreach($cardList as $card) {
                if ($card->getIsActive() && $card->getPaymentMethodCode() == self::METHOD_CODE1) {
                    if ($clientId && isset($getClient->paymentSources)) {
                        foreach ($getClient->paymentSources as $tokens) {
                            if($tokens->source == $card->getGatewayToken()){
                                $details = json_decode($card->getDetails(), true);
                                $creditcards[$card->getGatewayToken()] = [$details['type'].'-'.$details['maskedCC'].'('.$details['expirationDate'].')'];
                            }
                        }
                    }
                }
            }
        }
        $storeId = $this->configHelper->getStoreId();
        $mainConfig = $this->configHelper->isModuleEnabled($storeId);
        $mode = $this->configHelper->getPaymentMode($storeId);
        $publicKey = ($mode == 'live') ? $this->configHelper->getPublicKeyLive($storeId) : $this->configHelper->getPublicKeyTest($storeId);  
        $config = [
            'payment' =>  [
                self::METHOD_CODE1 =>  [
                    'active' => ($mainConfig) ? $this->configHelper->isCCEnabled($storeId) : 0,
                    'quote_id' => $this->dataHelper->getQuote()->getId(),
                    'description' => $this->configHelper->getCCDescription($storeId),
                    'public_key' => $publicKey,
                    'mode' => $mode,
                    'ccValues' => $creditcards,
                    'ccAvailable' => count($creditcards) > 0 ? 1 : 0,
                    'customer_id' => $customerId,
                    'msiValues' => $this->dataHelper->getMsiValues($this->dataHelper->getQuote()->getGrandTotal()),
                    'configManager' => $this->dataHelper->getConfigManager()
                ],
                self::METHOD_CODE2 => [
                    'active' => ($mainConfig) ? $this->configHelper->isCashEnabled($storeId) : 0,
                    'quote_id' => $this->dataHelper->getQuote()->getId(),
                    'description' => $this->configHelper->getCashDescription($storeId)
                ]
            ]
        ];
        return $config;
    }
}
