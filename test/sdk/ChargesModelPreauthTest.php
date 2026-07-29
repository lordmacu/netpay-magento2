<?php
/**
 * Standalone SDK test — no Magento, no PHPUnit. Run with plain php inside the container.
 * Verifies the Charges request model serializes transactionType / transactionTokenId,
 * and omits them (null) so the direct-sale request body is unchanged.
 */
$base = dirname(__DIR__, 2) . '/Sdk/lib';
require_once $base . '/Model/ModelInterface.php'; // Charges implements it — must load first (Charges.php:42)
require_once $base . '/Model/Charges.php';
require_once $base . '/ObjectSerializer.php';

use Netpay\Client\Model\Charges;
use Netpay\Client\ObjectSerializer;

$failures = [];

// Case 1: PreAuth fields serialize with the wire names NetPay documents.
$preauth = new Charges([
    'amount' => 100.50,
    'payment_method' => 'card',
    'transaction_type' => 'PreAuth',
]);
$wire = json_decode(json_encode(ObjectSerializer::sanitizeForSerialization($preauth)), true);
if (($wire['transactionType'] ?? null) !== 'PreAuth') {
    $failures[] = 'Case 1: transactionType missing or wrong: ' . json_encode($wire);
}
if (($wire['paymentMethod'] ?? null) !== 'card') {
    $failures[] = 'Case 1: existing paymentMethod mapping broke: ' . json_encode($wire);
}

// Case 2: PostAuth carries the transactionTokenId.
$postauth = new Charges([
    'amount' => 80.00,
    'payment_method' => 'card',
    'transaction_type' => 'PostAuth',
    'transaction_token_id' => 'abcd-1234',
]);
$wire = json_decode(json_encode(ObjectSerializer::sanitizeForSerialization($postauth)), true);
if (($wire['transactionType'] ?? null) !== 'PostAuth' || ($wire['transactionTokenId'] ?? null) !== 'abcd-1234') {
    $failures[] = 'Case 2: PostAuth fields wrong: ' . json_encode($wire);
}

// Case 3: direct sale (fields not set) must NOT include the new keys at all.
$sale = new Charges(['amount' => 50.00, 'payment_method' => 'card']);
$wire = json_decode(json_encode(ObjectSerializer::sanitizeForSerialization($sale)), true);
if (array_key_exists('transactionType', $wire) || array_key_exists('transactionTokenId', $wire)) {
    $failures[] = 'Case 3: null fields leaked into direct-sale body: ' . json_encode($wire);
}

// Case 4: getters/setters round-trip.
$m = new Charges();
$m->setTransactionType('ReAuth')->setTransactionTokenId('tok-1');
if ($m->getTransactionType() !== 'ReAuth' || $m->getTransactionTokenId() !== 'tok-1') {
    $failures[] = 'Case 4: getter/setter round-trip failed';
}

if ($failures) {
    foreach ($failures as $f) { fwrite(STDERR, "FAIL: $f\n"); }
    exit(1);
}
echo "OK — 4 cases passed\n";
