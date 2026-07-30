<?php

namespace Netpay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Netpay\Payment\Logger\Logger;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;
use Netpay\Payment\Helper\Data as DataHelper;

/**
 * Class ApiController
 */
class ApiController extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * Events NetPay emits to the registered webhook. Same contract the official NetPay
     * WooCommerce plugin implements in `netpay-checkout.php::process_ipn()` (hooked on its cash
     * IPN listener), which is the reference this module is ported from.
     *
     * Both are asynchronous "the customer has now paid" notifications — card is settled inline at
     * checkout, not through here. Anything else is logged and ignored rather than guessed at: a
     * non-payment event (a refund, a chargeback) would still read as a settled transaction on the
     * gateway and must not invoice the order.
     */
    const EVENT_OXXO_PAID = 'oxxopay.paid';
    /** SPEI / CEP (Comprobante Electrónico de Pago), settled through `GET /v3/transactions`. */
    const EVENT_CEP_PAID = 'cep.paid';

    /** @var JsonResultFactory */
    protected $jsonResultFactory;
    
    /** @var Logger */
    protected $logger;
    
    /** @var OrderCollection */
    protected $orderCollection;
    
    /** @var DataHelper */
    protected $dataHelper;

    /** @var ConfigHelper */
    protected $configHelper;
 
    /**
     * @param Context $context
     * @param JsonResultFactory $jsonResultFactory
     * @param Logger $logger
     * @param OrderCollection $orderCollection
     * @param DataHelper $dataHelper
     */
    /** @var StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        JsonResultFactory $jsonResultFactory,
        Logger $logger,
        OrderCollection $orderCollection,
        DataHelper $dataHelper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->orderCollection = $orderCollection;
        $this->dataHelper = $dataHelper;
        $this->storeManager = $storeManager;
    }

    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        try {
            if (!$this->getRequest()->isPost()) {
                $resultPage = $this->jsonResultFactory->create();
                $resultPage->setHttpResponseCode(404);
                return $resultPage;
            }

            $this->getResponse()->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $this->getResponse()->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-Token');
            $this->getResponse()->setHeader('Vary', 'Origin');
            $params = $this->getRequest()->getContent();
            $body = json_decode((string) $params, true);
            $result = $this->jsonResultFactory->create();
            try {
                $ip = $this->get_ip_addr();
                if (!$this->ip_in_range($ip)) {
                    $return = ['result' => 'error', 'message' => 'Error' . $ip];
                } elseif (!is_array($body)) {
                    // Malformed body: reject it as such, so it is distinguishable in the logs
                    // from a well-formed notification for an event we do not handle.
                    $return = ['result' => 'error', 'message' => 'Invalid payload'];
                } else {
                    $return = $this->handleEvent($body, $result);
                }
            } catch (\Exception $e) {
                $return = ['result' => 'error', 'message' => $e->getMessage()];
            }
            
            if (empty($return['result']) || $return['result'] == 'error') {
                $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            }

            $result->setData($return);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->debug('Webhook: ' . $e->getMessage());
            $errorResult = $this->jsonResultFactory->create();
            $errorResult->setHttpResponseCode(500);
            $errorResult->setData(['result' => 'error', 'message' => 'Internal error']);
            return $errorResult;
        }
    }
    
    /**
     * Dispatch a webhook notification to the handler for its event.
     *
     * NetPay emits two events (same contract the WooCommerce plugin implements): `cep.paid`
     * (SPEI/CEP) and `oxxopay.paid`. Anything else is acknowledged with 200 and logged, so NetPay
     * does not keep retrying a notification we do not act on.
     *
     * @param array $body   decoded notification
     * @param mixed $result json result, used to set the HTTP status
     * @return array
     */
    private function handleEvent(array $body, $result)
    {
        $data = (isset($body['data']) && is_array($body['data'])) ? $body['data'] : [];
        $event = (string) ($body['event'] ?? '');
        $transactionId = (string) ($data['transactionId'] ?? '');
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($event !== self::EVENT_OXXO_PAID && $event !== self::EVENT_CEP_PAID) {
            $this->logger->debug('Netpay webhook: unhandled event "' . $event . '"');
            $result->setHttpResponseCode(200);
            return ['result' => 'ignored', 'message' => 'Unhandled event ' . $event];
        }

        $order = $this->resolveOrder($event, $data);
        if ($order === null) {
            return [
                'result' => 'error',
                'message' => 'No Order for event ' . $event . ' (transactionId ' . $transactionId . ') exist!'
            ];
        }

        // Multi-store: a webhook POST carries no store context, so scope all config
        // (keys / mode / gateway URL) to the order's own store, not the default one.
        $this->storeManager->setCurrentStore($order->getStoreId());

        if (round((float) $order->getGrandTotal(), 2) != $amount) {
            return [
                'result' => 'error',
                'message' => 'The Order ' . $order->getIncrementId() . ' has a wrong amount!'
            ];
        }

        return $event === self::EVENT_OXXO_PAID
            ? $this->settleOxxo($order, $data, $transactionId, $amount, $result)
            : $this->settleTransaction($order, $transactionId, $amount, $result);
    }

    /**
     * Find the order a notification belongs to.
     *
     * The identifier differs per payment method, because `sales_order.token` holds a different
     * value in each (see ChargesApiManagement::getCharges):
     *   - non-OXXO : the transactionTokenId, which NetPay sends back as `data.transactionId`
     *   - OXXO     : the OXXO reference, which NetPay sends back as `data.reference`
     * `data.merchantRefCode` is the fallback for both: the SDK sends the order's entity id as
     * `billing.merchantReferenceCode` when creating the charge, and the WooCommerce plugin
     * resolves the OXXO notification through exactly that field.
     *
     * @param string $event
     * @param array  $data
     * @return \Magento\Sales\Model\Order|null
     */
    private function resolveOrder($event, array $data)
    {
        $order = $event === self::EVENT_OXXO_PAID
            ? $this->getOrderByToken((string) ($data['reference'] ?? ''))
            : $this->getOrderByToken((string) ($data['transactionId'] ?? ''));

        if ($order === null && isset($data['merchantRefCode'])) {
            $order = $this->getOrderById((int) $data['merchantRefCode']);
        }

        return $order;
    }

    /**
     * Reconcile a SPEI/CEP notification against the gateway.
     *
     * Never trusts the payload: it re-reads the transaction from NetPay and requires the same
     * three matches the WooCommerce plugin requires (status, transactionTokenId, amount) before
     * settling. On a terminal decline the still-pending order is cancelled so it is not orphaned.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $transactionId
     * @param float  $amount
     * @param mixed  $result
     * @return array
     */
    private function settleTransaction($order, $transactionId, $amount, $result)
    {
        $paymentManager = $this->dataHelper->getPaymentManager();
        $paymentManager->setUrlAttributes([$transactionId]);
        $transaction = $paymentManager->getOrder();

        $status = strtoupper((string) ($transaction->status ?? ''));
        $tokenMatches = ((string) ($transaction->transactionTokenId ?? '')) === $transactionId;
        $amountMatches = round((float) ($transaction->amount ?? 0), 2) == $amount;

        if (in_array($status, ['DONE', 'CHARGEABLE'], true)) {
            if (!$tokenMatches || !$amountMatches) {
                $this->logger->debug(
                    'Netpay webhook: transaction ' . $transactionId . ' is ' . $status
                    . ' but does not match the notification (token/amount); not settling.'
                );
                $result->setHttpResponseCode(200);
                return ['result' => 'ignored', 'message' => 'Transaction does not match the notification'];
            }
            // Idempotent: a repeated notification must not re-invoice or duplicate the note.
            /** @var \Magento\Sales\Model\Order\Payment|null $payment */
            $payment = $order->getPayment();
            $isPreauth = $payment && $payment->getAdditionalInformation('netpay_preauth');
            if ($isPreauth && !$payment->getAdditionalInformation('netpay_captured')) {
                if ($status === 'CHARGEABLE') {
                    // Pre-auth confirmed by webhook (e.g. after an async 3DS): register the
                    // authorization; the merchant captures from the admin invoice.
                    $this->dataHelper->registerAuthorization($order);
                } elseif ($status === 'DONE') {
                    // DONE without a Magento-side capture: the hold was captured outside
                    // Magento (NetPay dashboard). The flag must be set regardless of whether an
                    // invoice can still be created -- otherwise Netpay::void()'s netpay_captured
                    // guard never trips, and a later Void would refund money that was already
                    // captured (real money, no matching credit memo).
                    $payment->setAdditionalInformation('netpay_captured', true);
                    if ($order->canInvoice()) {
                        // Reconcile with an OFFLINE invoice — an online one would fire
                        // capture() and send a duplicate PostAuth.
                        $this->dataHelper->generateOfflineInvoice($order);
                        // Same two-statement form as the sibling branch below: chaining save()
                        // onto addCommentToStatusHistory() saves the history object, not the order.
                        $order->addCommentToStatusHistory(
                            'NetPay webhook: transaction captured outside Magento — order settled.'
                        );
                        $order->save();
                    } else {
                        // Nothing left to invoice (e.g. a manual Capture Offline already ran
                        // before this notification arrived) — just mark the payment captured so
                        // later guards (void/cancel) see the real state.
                        // addCommentToStatusHistory() returns the history object, not the order —
                        // save the ORDER explicitly so both the payment's netpay_captured flag and
                        // the comment (staged in-memory via addStatusHistory()) are persisted; a
                        // save chained onto the history object alone would only write the comment.
                        $order->addCommentToStatusHistory(
                            'NetPay webhook: transaction captured outside Magento — order was '
                            . 'already invoiced, marked as captured.'
                        );
                        $order->save();
                    }
                }
            } elseif ($order->canInvoice()) {
                $this->dataHelper->generateInvoice($order);
                $order->addCommentToStatusHistory(
                    'NetPay webhook: transaction ' . $status . ' — order settled.'
                )->save();
            }
            $result->setHttpResponseCode(200);
            return ['result' => 'success', 'message' => 'Order settled'];
        }

        if (in_array($status, ['FAILED', 'REJECT', 'REJECTED'], true)) {
            if ($order->canCancel()) {
                $order->registerCancellation(
                    'NetPay webhook: ' . ($transaction->responseMsg ?? $status)
                )->save();
            }
            $result->setHttpResponseCode(200);
            return ['result' => 'success', 'message' => 'Order cancelled'];
        }

        $result->setHttpResponseCode(200);
        return ['result' => 'ignored', 'message' => 'Transaction status ' . $status];
    }

    /**
     * Reconcile an OXXO notification against the gateway.
     *
     * Mirrors the WooCommerce plugin: read the OXXO transaction and require status `C` (paid)
     * plus a matching transaction id and amount before invoicing.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array  $data
     * @param string $transactionId
     * @param float  $amount
     * @param mixed  $result
     * @return array
     */
    private function settleOxxo($order, array $data, $transactionId, $amount, $result)
    {
        $transaction = $this->fetchOxxoTransaction($transactionId);
        $payloadReference = (string) ($data['reference'] ?? '');

        $paid = ((string) ($transaction->status ?? '')) === 'C'
            && ((string) ($transaction->transactionId ?? '')) === $transactionId
            && round((float) ($transaction->amount ?? 0), 2) == $amount
            // Only enforced when the notification carries a reference to compare against.
            && ($payloadReference === '' || ((string) ($transaction->reference ?? '')) === $payloadReference);

        if (!$paid) {
            return [
                'result' => 'error',
                'message' => 'OxxoPay transaction ' . $transactionId . ' is not payed in Netpay'
            ];
        }

        if ($order->canInvoice()) {
            $this->dataHelper->generateInvoice($order);
            $order->addCommentToStatusHistory(
                'NetPay webhook: OxxoPay transaction ' . $transactionId . ' paid.'
            )->save();
        }
        $result->setHttpResponseCode(200);
        return ['result' => 'success', 'message' => 'Successful Updated'];
    }

    /**
     * Read an OXXO transaction from the gateway (GET /v3/oxxopay/transaction/{id}).
     *
     * The vendored SDK exposes no OXXO transaction feature, so this is a direct call, scoped to
     * the order's store (the caller sets the current store before we resolve the keys/host).
     *
     * @param string $transactionId
     * @return \stdClass|null
     */
    protected function fetchOxxoTransaction($transactionId)
    {
        $storeId = $this->configHelper->getStoreId();
        $isTest = $this->configHelper->getPaymentMode($storeId) == 'test';
        $sk = $isTest
            ? $this->configHelper->getSecretKeyTest($storeId)
            : $this->configHelper->getSecretKeyLive($storeId);
        $baseUrl = $isTest
            ? 'https://gateway-154.netpaydev.com/gateway-ecommerce/'
            : 'https://suite.netpay.com.mx/gateway-ecommerce/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . 'v3/oxxopay/transaction/' . rawurlencode($transactionId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: ' . $sk]);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $raw = curl_exec($ch);

        return json_decode((string) $raw);
    }

    /**
     * get Order By Netpay Token
     *
     * @param string $token
     * @return
     */
    private function getOrderByToken($token)
    {
        // An empty token must never match: filtering on '' would pick up any order that has
        // no NetPay token at all.
        if ($token === '') {
            return null;
        }

        $collection = $this->orderCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('token', $token)
                ->setPageSize(1);
        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }

        return null;
    }

    /**
     * get Order by its entity id (NetPay's merchantRefCode)
     *
     * @param int $entityId
     * @return
     */
    private function getOrderById($entityId)
    {
        if ($entityId <= 0) {
            return null;
        }

        $collection = $this->orderCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('entity_id', $entityId)
                ->setPageSize(1);
        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }

        return null;
    }

    private function ip_in_range( $ip ) {
    $subnets = array(
    "200.53.144.45/32",
    "201.163.67.198/32",
    "173.245.48.0/20",
    "103.21.244.0/22",
    "103.22.200.0/22",
    "103.31.4.0/22",
    "141.101.64.0/18",
    "108.162.192.0/18",
    "190.93.240.0/20",
    "188.114.96.0/20",
    "197.234.240.0/22",
    "198.41.128.0/17",
    "162.158.0.0/15",
    "104.16.0.0/13",
    "104.24.0.0/14",
    "172.64.0.0/13",
    "131.0.72.0/22",
    "200.53.144.45",
    "200.53.144.37",
    "177.245.38.133",
    "34.215.108.207",
    "52.13.145.229",
    "187.190.172.50");
        for($i=0; $i<count($subnets); $i++) {
            $range = $subnets[$i];
            if ( strpos( $range, '/' ) === false ) {
                $range .= '/32';
            }
            list( $range, $netmask ) = explode( '/', $range, 2 );
            $range_decimal = ip2long( $range );
            $ip_decimal = ip2long( $ip );
            $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
            $netmask_decimal = ~ $wildcard_decimal;
            $is_exist = ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
            if($is_exist == 1) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Resolve the client IP for the webhook allowlist check.
     *
     * The decision must be anchored on the real TCP peer (REMOTE_ADDR), which a caller cannot
     * spoof — NOT on X-Forwarded-For / Client-IP, which are attacker-controlled request headers.
     * The previous version returned the raw forwarded header first, so anyone could send
     * `X-Forwarded-For: <a NetPay IP>` and pass the allowlist. We only fall back to the forwarded
     * chain when the peer is a local reverse proxy (loopback/private range), and then take the
     * first *public* address. This is defense-in-depth: the real anti-forgery control is the
     * server-to-server re-verification of the transaction against the gateway (see execute()).
     *
     * @return string
     */
    private function get_ip_addr() {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        if ($remote !== '' && $this->is_public_ip($remote)) {
            return $remote;
        }
        $forwarded = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? (string) $_SERVER['HTTP_X_FORWARDED_FOR']
            : (isset($_SERVER['HTTP_CLIENT_IP']) ? (string) $_SERVER['HTTP_CLIENT_IP'] : '');
        foreach (explode(',', $forwarded) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && $this->is_public_ip($candidate)) {
                return $candidate;
            }
        }
        return $remote !== '' ? $remote : '0.0.0.0';
    }

    /**
     * Whether $ip is a valid, publicly-routable address (not private/reserved/loopback).
     *
     * @param string $ip
     * @return bool
     */
    private function is_public_ip($ip) {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

}
