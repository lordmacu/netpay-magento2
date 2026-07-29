<?php

namespace Netpay\Payment\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

/**
 * Admin order view banner: state of the NetPay pre-authorization and how to capture it.
 */
class PreauthInfo extends Template
{
    /** @var Registry */
    private $coreRegistry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * Order currently open in the admin order view.
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Payment additional_information value, empty string when absent.
     *
     * @param string $key
     * @return mixed
     */
    public function getPaymentInfo($key)
    {
        $order = $this->getOrder();
        /** @var \Magento\Sales\Model\Order\Payment|null $payment */
        $payment = $order ? $order->getPayment() : null;
        return $payment ? $payment->getAdditionalInformation($key) : '';
    }

    /**
     * Only render for NetPay pre-auth orders.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $order = $this->getOrder();
        if (!$order
            || !$order->getPayment()
            || $order->getPayment()->getMethod() !== 'netpay'
            || !$this->getPaymentInfo('netpay_preauth')
        ) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Format an amount in the order's base currency.
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount($amount)
    {
        $order = $this->getOrder();
        return $order ? $order->getBaseCurrency()->formatTxt((float) $amount) : (string) $amount;
    }

    /**
     * Order creation date in the admin locale/timezone.
     *
     * The hold expiry is counted from it, so the raw UTC value stored in the order would mislead.
     *
     * @return string
     */
    public function getCreatedAtFormatted()
    {
        $order = $this->getOrder();
        if (!$order || !$order->getCreatedAt()) {
            return '';
        }
        return $this->formatDate($order->getCreatedAt(), \IntlDateFormatter::MEDIUM, true);
    }
}
