<?php

namespace Netpay\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    /**
     * Options for the pre-authorization mode select.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return
        [
            ['value' => 'authorize_capture', 'label' => __('Direct sale (charge immediately)')],
            ['value' => 'authorize', 'label' => __('Pre-authorization (hold now, capture on invoice)')]
        ];
    }
}
