<?php

namespace Netpay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Netpay\Payment\Model\Netpay;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Netpay\Payment\Logger\Logger;
use Netpay\Payment\Model\CustomerLinkRepository;
use Magento\Framework\View\Asset\Repository as AssetRepository;

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

    /** @var AssetRepository */
    private $assetRepo;

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
        Logger $logger,
        AssetRepository $assetRepo
    ) {
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->netpay = $netpay;
        $this->customerLinkRepository = $customerLinkRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->logger = $logger;
        $this->assetRepo = $assetRepo;
    }

    /**
     * Accepted card brand icons to show at checkout, gated by the admin toggles (cosmetic; matches
     * the WooCommerce plugin). Reuses Magento's core card-brand icons.
     *
     * @param int|null $storeId
     * @return array
     */
    private function getAcceptedCards($storeId)
    {
        $brands = [
            ['flag' => $this->configHelper->isVisaAccepted($storeId), 'label' => 'Visa', 'icon' => 'vi.png'],
            ['flag' => $this->configHelper->isMastercardAccepted($storeId), 'label' => 'Mastercard', 'icon' => 'mc.png'],
            ['flag' => $this->configHelper->isAmexAccepted($storeId), 'label' => 'American Express', 'icon' => 'ae.png'],
        ];
        $accepted = [];
        foreach ($brands as $brand) {
            if ($brand['flag']) {
                $accepted[] = [
                    'label' => $brand['label'],
                    'icon' => $this->assetRepo->getUrl('Magento_Payment::images/cc/' . $brand['icon']),
                ];
            }
        }
        return $accepted;
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
                    'configManager' => $this->dataHelper->getConfigManager(),
                    'acceptedCards' => $this->getAcceptedCards($storeId)
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
