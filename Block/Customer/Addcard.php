<?php

namespace Netpay\Payment\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\View\Element\Template\Context;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Helper\Config as ConfigHelper;

class Addcard extends Template
{
    /** @var DataHelper */
    protected $dataHelper;

    protected $customerRepositoryInterface;

    protected $paymentTokenManagement;

    /** @var ConfigHelper */
    protected $configHelper;

    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->dataHelper = $dataHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->configHelper = $configHelper;
    }

    /**
     * Public key for the configured mode (test/live).
     *
     * @return string
     */
    public function getPublicKey()
    {
        $storeId = $this->configHelper->getStoreId();
        return $this->configHelper->getPaymentMode($storeId) === 'live'
            ? (string) $this->configHelper->getPublicKeyLive($storeId)
            : (string) $this->configHelper->getPublicKeyTest($storeId);
    }

    /**
     * @return bool
     */
    public function isSandboxMode()
    {
        return $this->configHelper->getPaymentMode($this->configHelper->getStoreId()) !== 'live';
    }

    public function showAddCardButton() {
        $customerId = $this->dataHelper->getQuote()->getCustomerId();
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);
        $cards = 0;
        foreach($cardList as $card) {
            if ($card->getPaymentMethodCode() == 'netpay' && $card->getIsActive()) {
                $cards++;
            }
        }
        if ($cards == 0 || $cards > 3) {
            return false;
        } else {
            return true;
        }
    }
}