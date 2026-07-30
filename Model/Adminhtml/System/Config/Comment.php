<?php

namespace Netpay\Payment\Model\Adminhtml\System\Config;

use Magento\Framework\UrlInterface;

/**
 * Class IsCashEnable
 *
 * This is ajax controller
 * It executes and get cash enable or not from api call
 */
class Comment implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getBaseUrl() . 'netpay/payment/apicontroller';

        return (string) __(
            'URL where NetPay notifies asynchronous payments (OXXO). For this store it is %1. '
            . 'It is registered per NetPay account with the button below, and NetPay must be able '
            . 'to reach it over HTTPS. Card payments are settled during checkout and are not '
            . 'notified here.',
            $url
        );
    }
}
