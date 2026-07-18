<?php
namespace Netpay\Payment\Model;

use Netpay\Payment\Helper\Data as DataHelper;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Payment\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Netpay\Payment\Logger\Logger as NetpayLogger;

/**
 * Class Netpay
 *
 * Payment method class which will extend all feature of magento basic methods
 */
class Netpay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /** @var string */
    protected $_code = 'netpay';
     
    /** @var bool */
    protected $_canCapture = true;
     
    /** @var DataHelper */
    protected $dataHelper;

    /** @var NetpayLogger */
    protected $netpayLogger;
    
    /**
     * openpay constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param DataHelper                 $dataHelper
     * @param NetpayLogger               $netpayLogger
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        DataHelper $dataHelper,
        NetpayLogger $netpayLogger,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->dataHelper = $dataHelper;
        $this->netpayLogger = $netpayLogger;
    }
    
    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }
}