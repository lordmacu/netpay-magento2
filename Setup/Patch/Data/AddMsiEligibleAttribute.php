<?php

namespace Netpay\Payment\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds the "netpay_msi" product attribute so a merchant can exclude specific products from
 * Meses sin Intereses (installments), mirroring the WooCommerce plugin's "promotion_products".
 * Default is Yes (eligible) — MSI keeps working for every product until one is opted out.
 */
class AddMsiEligibleAttribute implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'netpay_msi',
            [
                'type' => 'int',
                'label' => 'Aplica Meses sin Intereses (NetPay)',
                'input' => 'boolean',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'default' => '1',
                'required' => false,
                'user_defined' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General',
                'used_in_product_listing' => true,
                'visible' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'apply_to' => '',
            ]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
