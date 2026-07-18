<?php

declare(strict_types=1);

/**
 * Netpay_Payment — module registration + self-contained SDK autoloader.
 *
 * The NetPay PHP SDK (namespaces `Netpay\Client\` and `BusinessLayer\Netpay\`) is a separate
 * Swagger-generated package (`netpay/custom`). To ship this module self-contained (a drop-in,
 * with no `composer require netpay/custom`), the SDK is vendored under Sdk/ and its PSR-4
 * namespaces are registered here. The layout under Sdk/ is preserved (lib/ businessLayer/ config/)
 * because BusinessLayer\Netpay\Utilities\Constants resolves config.ini as __DIR__/../../config.
 * Guzzle (guzzlehttp/guzzle) is NOT vendored: Magento already ships and autoloads it.
 */

$netpaySdkPrefixes = [
    'Netpay\\Client\\' => __DIR__ . '/Sdk/lib/',
    'BusinessLayer\\Netpay\\' => __DIR__ . '/Sdk/businessLayer/',
];

spl_autoload_register(static function (string $class) use ($netpaySdkPrefixes): void {
    foreach ($netpaySdkPrefixes as $prefix => $baseDir) {
        $length = strlen($prefix);
        if (strncmp($class, $prefix, $length) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $length);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($file)) {
            // phpcs:ignore Magento2.Security.IncludeFile -- self-contained SDK autoloader, fixed base dirs.
            require $file;
        }
        return;
    }
});

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Netpay_Payment',
    __DIR__
);
