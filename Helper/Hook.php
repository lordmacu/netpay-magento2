<?php

namespace Netpay\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Hook
 */
class Hook extends AbstractHelper
{
    /*protected function checkSignature() {
        $php = Mage::app()->getRequest()->getParam('php', false);
        $shop = Mage::app()->getRequest()->getParam('shop', false);
        $plugin = Mage::app()->getRequest()->getParam('plugin', false);
        IF ($php && $shop && $plugin) {
                if ($php == phpversion() && $shop == CPHookResponse::getSignatureShop() && $plugin == CPHookResponse::getModuleVersion()) {
                        return true;
                }
                CPErrorHandler::handle(CPErrors::RESULT_SIGNATURE_MISMATCH, "Signature changed", "Signature changed \n" . $php . " -> " . phpversion() . "\n" . $shop . " -> " . CPHookResponse::getSignatureShop() . "\n" . $plugin . " -> " . CPHookResponse::getModuleVersion());
        } else {
                CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "Missing params for signature check", "Missing params for signature check");
        }
    }*/

}
