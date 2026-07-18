<?php

namespace Netpay\Payment\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

/**
 * Class Disable
 *
 * This class will make some of the fields disabled on the admin openpay form
 */
class Disable extends Field
{
    /**
     * {@inheritDoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('readonly', 1);
        return $element->getElementHtml();
    }
}
