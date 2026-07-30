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

    /** @var bool Partial invoice (reduced qty) captures the invoice total via PostAuth. */
    protected $_canCapturePartial = true;

    /** @var bool Void releases the NetPay hold (same endpoint as refund). */
    protected $_canVoid = true;

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

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        if (!$payment->getAdditionalInformation('netpay_preauth')) {
            // Direct sale: the gateway already charged the card before the invoice was
            // created (legacy flow) — Capture Online only records the payment in Magento.
            return $this;
        }

        $order = $payment->getOrder();
        $transactionTokenId = (string) $order->getData('token');
        $sourceToken = (string) $payment->getAdditionalInformation('netpay_source_token');
        if ($transactionTokenId === '' || $sourceToken === '' || !$payment->getAdditionalInformation('netpay_authorized')) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This order has no confirmed NetPay pre-authorization to capture.')
            );
        }
        if ($payment->getAdditionalInformation('netpay_captured')) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The NetPay pre-authorization of this order was already captured.')
            );
        }

        // NetPay's PostAuth accepts a final amount within ±20% of the pre-authorized one.
        // https://docs.netpay.com.mx/reference/check-out-post-autorizacion
        $amount = round((float) $amount, 2);
        $authorized = (float) $payment->getAdditionalInformation('netpay_preauth_amount');
        $min = round($authorized * 0.80, 2);
        $max = round($authorized * 1.20, 2);
        if ($authorized > 0 && ($amount < $min || $amount > $max)) {
            throw new \Magento\Framework\Exception\LocalizedException(__(
                'NetPay only allows capturing between %1 and %2 (±20% of the %3 pre-authorized).'
                . ' For a lower amount: cancel the order (the hold is released) and charge again.',
                $order->getBaseCurrency()->formatTxt($min),
                $order->getBaseCurrency()->formatTxt($max),
                $order->getBaseCurrency()->formatTxt($authorized)
            ));
        }

        // Multi-store: capture against the order's own NetPay account.
        $this->storeManager->setCurrentStore($order->getStoreId());

        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $others = new \Magento\Framework\DataObject();
            $others->transactionType = 'PostAuth';
            $others->transactionTokenId = $transactionTokenId;
            $others->source = $sourceToken;
            $others->amount = $amount;
            $others->currency = (string) $order->getBaseCurrencyCode();
            $others->description = 'Captura de la orden ' . $order->getIncrementId();
            $others->paymentmethod = 'card';
            // NetPay's PostAuth returns a bare HTTP 500 (no validation message) when the
            // billing block is missing entirely; email/firstName/lastName non-blank is enough
            // (confirmed live against the sandbox). Not sent by the checkout-time charge because
            // that request goes through Shopdata's cart mapping instead of this hand-built object.
            $billingAddress = $order->getBillingAddress();
            $others->billingEmail = (string) $order->getCustomerEmail();
            $others->billingFirstName = $billingAddress
                ? (string) $billingAddress->getFirstname()
                : (string) $order->getCustomerFirstname();
            $others->billingLastName = $billingAddress
                ? (string) $billingAddress->getLastname()
                : (string) $order->getCustomerLastname();
            $paymentManager->setShopdata(null, $others);
            $response = $paymentManager->getCapture();
        } catch (\Exception $e) {
            $this->netpayLogger->debug('NetPay PostAuth failed for ' . $transactionTokenId . ': ' . $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The NetPay capture could not be processed: %1', $e->getMessage())
            );
        }

        $status = strtolower((string) ($response->status ?? ''));
        if (!in_array($status, ['success', 'done'], true)) {
            $message = (string) ($response->responseMsg ?? ($response->message ?? $status));
            $this->netpayLogger->debug('NetPay PostAuth non-success for ' . $transactionTokenId . ': ' . $message);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('NetPay did not approve the capture (%1).', $message)
            );
        }

        $payment->setAdditionalInformation('netpay_captured', true);
        $payment->setTransactionId($transactionTokenId . '-capture');
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $order->addCommentToStatusHistory(
            'NetPay: PostAuth captured ' . $order->getBaseCurrency()->formatTxt($amount)
            . ' (pre-authorized: ' . $order->getBaseCurrency()->formatTxt($authorized) . ').'
        );

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

    /**
     * Void an uncaptured NetPay pre-authorization (admin "Void" button).
     * Releases the hold via NetPay's cancelation endpoint (same as refund).
     * https://docs.netpay.com.mx/reference/cancelaciones-post-autorizacion
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        // Explicit admin action: a captured payment must NEVER be reported as voided while the
        // money stays charged. VoidPayment::execute() calls void() without re-checking canVoid()
        // server-side (the button is normally hidden, but a stale page/direct POST/race still
        // reaches this), so this guard must fail loudly here -- unlike cancel() below, which keeps
        // this silent because order cancelation must never be blocked. Same pattern capture() uses
        // for a double-capture attempt.
        if ($payment->getAdditionalInformation('netpay_captured')) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This NetPay payment was already captured and cannot be voided. Issue a refund (credit memo) instead.')
            );
        }
        $this->releasePreauthHold($payment, true);
        return $this;
    }

    /**
     * Order cancelation hook. Best-effort hold release: never blocks the cancelation
     * (an unreleased hold auto-expires in ~5 business days anyway).
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->releasePreauthHold($payment, false);
        return $this;
    }

    /**
     * Release the NetPay hold of a confirmed, uncaptured pre-authorization.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param bool $strict Throw on gateway failure (explicit void) or just log (order cancel).
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function releasePreauthHold($payment, $strict)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        if (!$payment->getAdditionalInformation('netpay_authorized')
            || $payment->getAdditionalInformation('netpay_captured')
            || $payment->getAdditionalInformation('netpay_hold_released')
        ) {
            // Nothing to release: charge never confirmed, already captured, or already released.
            return;
        }
        $order = $payment->getOrder();
        $token = (string) $order->getData('token');
        if ($token === '') {
            return;
        }

        $this->storeManager->setCurrentStore($order->getStoreId());
        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $paymentManager->setUrlAttributes([$token]);
            $response = $paymentManager->refund();
            $status = strtolower((string) ($response->status ?? ''));
            // Live-verified against the sandbox (Task 7): NetPay's cancelation/refund endpoint
            // releases an UNCAPTURED hold and reports "CANCELED" (not "success"/"done"/"refunded"/
            // "reversed" as the other transaction states in this module use) -- confirming the hold
            // actually moved off CHARGEABLE, read back independently via GetOrder.
            // An empty/unrecognized status is treated as NOT confirmed (fix round 1): a bare "" used
            // to short-circuit this check and fall through to setting netpay_hold_released, which
            // would permanently suppress any retry even though nothing was actually confirmed.
            if (!in_array($status, ['success', 'done', 'refunded', 'reversed', 'canceled', 'cancelled'], true)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('NetPay did not confirm the hold release (status: %1).', $status !== '' ? $status : '(empty)')
                );
            }
        } catch (\Exception $e) {
            $this->netpayLogger->debug('NetPay hold release failed for ' . $token . ': ' . $e->getMessage());
            if ($strict) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The NetPay hold could not be released: %1', $e->getMessage())
                );
            }
            $order->addCommentToStatusHistory(
                'NetPay: hold release failed (' . $e->getMessage() . '). The hold will auto-expire in ~5 business days.'
            );
            return;
        }

        $payment->setAdditionalInformation('netpay_hold_released', true);
        $order->addCommentToStatusHistory('NetPay: pre-authorization hold released (' . $token . ').');
    }
}