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
use Magento\Store\Model\StoreManagerInterface;

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

    /** @var bool */
    protected $_canRefund = true;

    /** @var bool */
    protected $_canRefundInvoicePartial = false;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var NetpayLogger */
    protected $netpayLogger;

    /** @var StoreManagerInterface */
    protected $storeManager;
    
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
        StoreManagerInterface $storeManager,
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
        $this->storeManager = $storeManager;
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

    /**
     * Refund the NetPay transaction for an online credit memo.
     *
     * NetPay's refund endpoint (POST /v3/transactions/{id}/refund) refunds the full transaction and
     * takes no amount, so only full refunds are supported.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $transactionId = $order->getData('token');
        if (empty($transactionId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('No NetPay transaction is associated with this order.')
            );
        }

        // NetPay refunds the full transaction; reject partial refunds instead of over-refunding.
        $orderTotal = (float) $order->getGrandTotal();
        $baseTotal = (float) $order->getBaseGrandTotal();
        $amount = (float) $amount;
        if (abs($amount - $orderTotal) > 0.001 && abs($amount - $baseTotal) > 0.001) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('NetPay only supports full refunds; partial refunds are not available.')
            );
        }

        // Multi-store: refund against the order's own NetPay account.
        $this->storeManager->setCurrentStore($order->getStoreId());

        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $paymentManager->setUrlAttributes([$transactionId]);
            $response = $paymentManager->refund();
        } catch (\Exception $e) {
            $this->netpayLogger->debug('NetPay refund failed for ' . $transactionId . ': ' . $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The NetPay refund could not be processed. Please verify the transaction in NetPay.')
            );
        }

        $status = strtolower((string) ($response->status ?? ''));
        if ($status !== '' && !in_array($status, ['success', 'done', 'refunded', 'reversed'], true)) {
            $this->netpayLogger->debug('NetPay refund non-success status for ' . $transactionId . ': ' . $status);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The NetPay refund was not confirmed (status: %1).', $status)
            );
        }

        return $this;
    }
}