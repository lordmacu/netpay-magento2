<?php

namespace Netpay\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Netpay\Payment\Logger\Logger as NetpayLogger;

/**
 * Sends the invoice email of a captured pre-authorization: the customer's final receipt.
 */
class SendCaptureInvoiceEmail implements ObserverInterface
{
    /** @var InvoiceSender */
    private $invoiceSender;

    /** @var NetpayLogger */
    private $netpayLogger;

    /**
     * @param InvoiceSender $invoiceSender
     * @param NetpayLogger $netpayLogger
     */
    public function __construct(InvoiceSender $invoiceSender, NetpayLogger $netpayLogger)
    {
        $this->invoiceSender = $invoiceSender;
        $this->netpayLogger = $netpayLogger;
    }

    /**
     * Email the customer the REAL captured amount of a pre-authorized NetPay order.
     *
     * The native invoice email lists only the invoiced items and the invoice total, which is
     * exactly the "final receipt" of the pre-auth flow. Fires for both the admin Capture Online
     * invoice and the webhook offline reconcile invoice.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        if (!$invoice || !$invoice->getId() || (int) $invoice->getState() !== Invoice::STATE_PAID) {
            return;
        }
        $order = $invoice->getOrder();
        /** @var \Magento\Sales\Model\Order\Payment|null $payment */
        $payment = $order ? $order->getPayment() : null;
        if (!$payment
            || $payment->getMethod() !== 'netpay'
            || !$payment->getAdditionalInformation('netpay_preauth')
            || $invoice->getEmailSent()
        ) {
            return;
        }
        try {
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            // Never break the capture because the email failed.
            $this->netpayLogger->debug(
                'NetPay: capture invoice email failed for order ' . $order->getIncrementId() . ': ' . $e->getMessage()
            );
        }
    }
}
