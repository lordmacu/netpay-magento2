<?php

namespace Netpay\Payment\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Button
 *
 * custom button class for implementing set up job on payment form in admin
 */
class Button extends Field
{
    /** @var string */
    protected $_template = 'Netpay_Payment::system/config/button.phtml';
    
    /**
     * button constructor
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * custom url for executing controller
     *
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->getUrl('netpay_admin/system_config/iscashenable');
    }
    
    /**
     * {@inheritDoc}
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
        ->setData(
            [
                'id' => 'activate_cash',
                'label' => __('Activate/Deactivate OxxoPay'),
                'class' => "button"
            ]
        );
        return $button->toHtml();
    }
}
