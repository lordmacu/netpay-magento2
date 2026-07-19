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
            $body = json_decode($params, true);
            $result = $this->jsonResultFactory->create();            
            try {
                $ip = $this->get_ip_addr();
                $ip_in_subnet = $this->ip_in_range( $ip);

                if($ip_in_subnet){
                    $token = $body['data']['reference'];
                    $transactionId = $body['data']['transactionId'];
                    $amount = round((float)$body['data']['amount'], 2);
                    $event = $body['event'];
                    $order = $this->getOrderByToken($token);
                    if ($order !== null) {
                        // Multi-store: a webhook POST carries no store context, so scope all config
                        // (keys / mode / gateway URL) to the order's own store, not the default one.
                        $this->storeManager->setCurrentStore($order->getStoreId());
                    }
                    if ($order == null) {
                        $return = ['result' => 'error', 'message' => 'No Order with the token ' . $token . ' exist!'];
                    } elseif (round((float)$order->getGrandTotal(), 2) != $amount) {
                        $return = ['result' => 'error', 'message' => 'The Order with the token ' . $token . ' has a wrong amount!'];
                    }elseif ($event == 'oxxopay.paid') {
                        $storeId = $this->configHelper->getStoreId();
                        $sk = ($this->configHelper->getPaymentMode($storeId) == 'test') ? $this->configHelper->getSecretKeyTest($storeId) : $this->configHelper->getSecretKeyLive($storeId);
                        $header = array('Content-Type: application/json','Authorization: ' . $sk);
                        $baseUrl = ($this->configHelper->getPaymentMode($storeId) == 'test') ? 'https://gateway-154.netpaydev.com/gateway-ecommerce/' : 'https://suite.netpay.com.mx/gateway-ecommerce/';
                        $url = $baseUrl . 'v3/oxxopay/transaction/' . $transactionId;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url); 
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
                        curl_setopt($ch, CURLOPT_HEADER, 0); 
                        $a1 = curl_exec($ch);
                        curl_close($ch); 
                        $data = json_decode($a1);
                        if($data->status == "C" && $data->transactionId == $transactionId && $data->amount == $amount && $data->reference == $token){
                            $this->dataHelper->generateInvoice($order);
                            $return = ['result' => 'success', 'message' => 'Successful Updated'];
                            $result->setHttpResponseCode(200);
                        }else{
                            $return = ['result' => 'error', 'message' => 'Order has the status2' . $event . ' in Netpay'];
                        }
                    } else {
                        // Card (or any non-OXXO) event: don't trust the webhook payload's status.
                        // Re-verify the transaction against the gateway (source of truth) using the
                        // order's own token and reconcile — settle on DONE/CHARGEABLE, cancel on
                        // FAILED/REJECT. Robust to NetPay's exact card webhook event name/shape.
                        $paymentManager = $this->dataHelper->getPaymentManager();
                        $paymentManager->setUrlAttributes([$token]);
                        $transaction = $paymentManager->getOrder();
                        $status = strtoupper((string) ($transaction->status ?? ''));
                        if (in_array($status, ['DONE', 'CHARGEABLE'], true)) {
                            if ($order->canInvoice()) {
                                $this->dataHelper->generateInvoice($order);
                            }
                            // Leave an audit-trail note on the order (matches the WooCommerce plugin).
                            $order->addCommentToStatusHistory(
                                'NetPay webhook: transaction ' . $status . ' — order settled.'
                            )->save();
                            $return = ['result' => 'success', 'message' => 'Order settled'];
                            $result->setHttpResponseCode(200);
                        } elseif (in_array($status, ['FAILED', 'REJECT', 'REJECTED'], true)) {
                            if ($order->canCancel()) {
                                $order->registerCancellation(
                                    'NetPay webhook: ' . ($transaction->responseMsg ?? $status)
                                )->save();
                            }
                            $return = ['result' => 'success', 'message' => 'Order cancelled'];
                            $result->setHttpResponseCode(200);
                        } else {
                            $return = ['result' => 'ignored', 'message' => 'Transaction status ' . $status];
                            $result->setHttpResponseCode(200);
                        }
                    }
                } else{
                    $return = ['result' => 'error', 'message' => 'Error' . $ip];
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
     * get Order By Netpay Token
     * 
     * @param string $token
     * @return
     */
    private function getOrderByToken($token)
    {
        $collection = $this->orderCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('token', $token);
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
    
    private function get_ip_addr() {
        return isset($_SERVER['HTTP_CLIENT_IP']) 
        ? $_SERVER['HTTP_CLIENT_IP'] 
        : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) 
          ? $_SERVER['HTTP_X_FORWARDED_FOR'] 
          : $_SERVER['REMOTE_ADDR']);
    }

}
