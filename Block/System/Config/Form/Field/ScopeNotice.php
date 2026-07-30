<?php

namespace Netpay\Payment\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Netpay\Payment\Helper\Config as ConfigHelper;

/**
 * Class ScopeNotice
 *
 * Renders a notice at the top of the NetPay credentials group stating which scope is being
 * edited and whether the credentials shown belong to that scope or are inherited.
 *
 * The config form looks identical for Default Config, a website and a store view, while each of
 * them may hold a different NetPay account. Since the secret keys render as `******`, there is
 * otherwise no way to tell from the screen whose credentials are on display.
 */
class ScopeNotice extends Field
{
    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ConfigDataCollectionFactory */
    private $configDataCollectionFactory;

    /**
     * @param Context                     $context
     * @param StoreManagerInterface       $storeManager
     * @param ConfigDataCollectionFactory $configDataCollectionFactory
     * @param array                       $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Render the notice across the full row, without the usual label/scope checkbox chrome.
     *
     * {@inheritDoc}
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return '<tr id="row_' . $element->getHtmlId() . '"><td colspan="4">'
            . $this->buildNotice()
            . '</td></tr>';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->buildNotice();
    }

    /**
     * @return string
     */
    private function buildNotice()
    {
        list($scopeName, $scopeType, $scopeId) = $this->resolveScope();
        $hasOwnKeys = $this->hasOwnCredentials($scopeType, $scopeId);

        $intro = (string) __('You are editing the NetPay credentials of %1.', '<strong>' . $this->escapeHtml($scopeName) . '</strong>');

        if ($scopeType === 'default') {
            $detail = (string) __(
                'These credentials apply to every store that does not define its own. If your stores '
                . 'use separate NetPay accounts, set each one from its website scope using the store '
                . 'switcher above.'
            );
        } elseif ($hasOwnKeys) {
            $detail = (string) __(
                'This scope has its own credentials, so it operates against its own NetPay account.'
            );
        } else {
            $detail = (string) __(
                'This scope has no credentials of its own: it inherits them from the default scope '
                . 'and therefore shares that NetPay account. Uncheck "Use Default" to give it a '
                . 'separate account.'
            );
        }

        $warning = (string) __(
            'The <em>Update in Netpay</em> button registers the webhook against the account of the '
            . 'scope selected here.'
        );

        return '<div class="message message-notice" style="margin:0 0 1rem">'
            . '<span>' . $intro . ' ' . $detail . '</span><br/><span>' . $warning . '</span>'
            . '</div>';
    }

    /**
     * Which scope the config form is currently editing.
     *
     * @return array{0:string,1:string,2:int} name, type (default|websites|stores), id
     */
    private function resolveScope()
    {
        $storeParam = $this->getRequest()->getParam('store');
        if (!empty($storeParam)) {
            try {
                $store = $this->storeManager->getStore($storeParam);

                return [(string) __('store view %1', $store->getName()), 'stores', (int) $store->getId()];
            } catch (\Exception $e) {
                return [(string) __('the selected store view'), 'stores', 0];
            }
        }

        $websiteParam = $this->getRequest()->getParam('website');
        if (!empty($websiteParam)) {
            try {
                $website = $this->storeManager->getWebsite($websiteParam);

                return [(string) __('website %1', $website->getName()), 'websites', (int) $website->getId()];
            } catch (\Exception $e) {
                return [(string) __('the selected website'), 'websites', 0];
            }
        }

        return [(string) __('Default Config'), 'default', 0];
    }

    /**
     * Whether this scope overrides the credentials instead of inheriting them.
     *
     * Checked against core_config_data rather than by comparing resolved values, because two
     * scopes legitimately holding the same key would otherwise read as "inherited".
     *
     * @param string $scopeType
     * @param int    $scopeId
     * @return bool
     */
    private function hasOwnCredentials($scopeType, $scopeId)
    {
        if ($scopeType === 'default') {
            return true;
        }

        try {
            $collection = $this->configDataCollectionFactory->create()
                ->addFieldToFilter('scope', $scopeType)
                ->addFieldToFilter('scope_id', $scopeId)
                ->addFieldToFilter('path', ['in' => [
                    ConfigHelper::XML_PATH_PUBLIC_KEY_TEST,
                    ConfigHelper::XML_PATH_SECRET_KEY_TEST,
                    ConfigHelper::XML_PATH_PUBLIC_KEY_LIVE,
                    ConfigHelper::XML_PATH_SECRET_KEY_LIVE,
                ]]);

            return $collection->getSize() > 0;
        } catch (\Exception $e) {
            // Never let a cosmetic notice break the config screen.
            return false;
        }
    }
}
