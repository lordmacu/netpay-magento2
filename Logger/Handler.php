<?php

namespace Netpay\Payment\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 */
class Handler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/netpaymagento.log';
}
