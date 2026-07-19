<?php

namespace Netpay\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 *
 * Get admin configuration values as well as has some common functions
 * which we use on all other files
 */
class Config extends AbstractHelper
{
    /** @var string */
    const XML_PATH_IS_ENABLE = 'payment/netpay/enable';
    
    /** @var string */
    const XML_PATH_PAYMENT_MODE = 'payment/netpay/payment_mode';
    
    /** @var string */
    const XML_PATH_IS_CC_ENABLE = 'payment/netpay/active';
    
    /** @var string */
    const XML_PATH_CC_TITLE = 'payment/netpay/title';
    
    /** @var string */
    const XML_PATH_CC_DESCRIPTION = 'payment/netpay/description';

    const XML_PATH_ACCEPT_VISA = 'payment/netpay/accept_visa';

    const XML_PATH_ACCEPT_MASTERCARD = 'payment/netpay/accept_mastercard';

    const XML_PATH_ACCEPT_AMEX = 'payment/netpay/accept_amex';
    
    /** @var string */
    const XML_PATH_IS_CASH_ENABLE = 'payment/netpaycash/active';
    
    /** @var string */
    const XML_PATH_CASH_TITLE = 'payment/netpaycash/title';
    
    /** @var string */
    const XML_PATH_CASH_DESCRIPTION = 'payment/netpaycash/description';
    
    /** @var string */
    const XML_PATH_CASH_WEBHOOK = 'payment/netpaycash/webhook';

    /** @var string */
    const XML_PATH_PUBLIC_KEY_TEST = 'payment/netpay/public_key_test';

    /** @var string */
    const XML_PATH_SECRET_KEY_TEST = 'payment/netpay/secret_key_test';

    /** @var string */
    const XML_PATH_PUBLIC_KEY_LIVE = 'payment/netpay/public_key_live';

    /** @var string */
    const XML_PATH_SECRET_KEY_LIVE = 'payment/netpay/secret_key_live';

    /** @var StoreManagerInterface */
    protected $storeManager;
    
    /**
     * config constructor
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $context->getScopeConfig();
    }
    
    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * Get module is enable or disable
     *
     * @return bool
     */
    public function isModuleEnabled($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_IS_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get payment mode whether it is test or live
     *
     * @param int
     * 
     * @return string
     */
    public function getPaymentMode($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_PAYMENT_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get Credit Card is enable or disable
     *
     * @return bool
     */
    public function isCCEnabled($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_IS_CC_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether the Visa brand icon is shown at checkout (cosmetic; matches the WooCommerce plugin).
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isVisaAccepted($storeId = null)
    {
        return (bool) $this->getScopeConfig()->getValue(
            self::XML_PATH_ACCEPT_VISA,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether the Mastercard brand icon is shown at checkout (cosmetic).
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMastercardAccepted($storeId = null)
    {
        return (bool) $this->getScopeConfig()->getValue(
            self::XML_PATH_ACCEPT_MASTERCARD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether the American Express brand icon is shown at checkout (cosmetic).
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAmexAccepted($storeId = null)
    {
        return (bool) $this->getScopeConfig()->getValue(
            self::XML_PATH_ACCEPT_AMEX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * get title of the payment method Credit Card
     *
     * @return string
     */
    public function getCCTitle($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_CC_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get description of the payment method Credit Card
     *
     * @return string
     */
    public function getCCDescription($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_CC_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get Cash is enable or disable
     *
     * @return string
     */
    public function isCashEnabled($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_IS_CASH_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * get title of the payment method Cash 
     *
     * @return string
     */
    public function getCashTitle($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_CASH_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get description of the payment method Cash
     *
     * @return string
     */
    public function getCashDescription($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_CASH_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get Webhook URL of the payment method Cash
     *
     * @return string
     */
    public function getCashWebhook($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_CASH_WEBHOOK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get public key
     * 
     * @return string
     */
    public function getPublicKeyTest($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_PUBLIC_KEY_TEST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get secret key
     * 
     * @return string
     */
    public function getSecretKeyTest($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_SECRET_KEY_TEST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get public key
     * 
     * @return string
     */
    public function getPublicKeyLive($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_PUBLIC_KEY_LIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get secret key
     * 
     * @return string
     */
    public function getSecretKeyLive($storeId = null)
    {
        return $this->getScopeConfig()->getValue(
            self::XML_PATH_SECRET_KEY_LIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get Credit Card of a customer
     *
     * @return array
     */
    public function getCreditCards()
    {
        $cc = [];
        $cc[0] = "VISA";
        $cc[1] = "MasterCard";
        $cc[2] = "eCard";

        return $cc;
    }

}
