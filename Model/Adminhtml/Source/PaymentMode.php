<?php

namespace Netpay\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentMode
 */
class PaymentMode implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return
        [
            ['value' => 'test', 'label' => __('Test')],
            ['value' => 'live', 'label' => __('Live')]
        ];
    }
}
