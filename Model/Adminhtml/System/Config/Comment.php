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
        $url = $this->urlInterface->getBaseUrl();

        return 'Insert a Hook URL. For this Shop you will find the Hook in ' . $url . 'netpay/payment/apicontroller';
    }
}
