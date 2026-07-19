<?php

namespace Netpay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Netpay\Payment\Helper\Data as DataHelper;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Netpay\Payment\Logger\Logger;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;

/**
 * Class Reside
 *
 */
class Reside extends Action
{   
    /** @var CheckoutSession */
    protected $checkoutSession;
    
    /** @var StoreManager */
    protected $storeManager;
    
    /** @var DataHelper */
    protected $dataHelper;
    
    /** @var CartManagementInterface */
    protected $quoteManagement;
    
    /** @var ManagerInterface */
    protected $messageManager;
    
    /** @var OrderRepository */
    protected $orderRepository;
    
    /** @var Logger */
    protected $logger;
    
    /** @var OrderCollection */
    protected $orderCollection;
    
    /**
     * 
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param StoreManager $storeManager
     * @param DataHelper $dataHelper
     * @param CartManagementInterface $quoteManagement
     * @param ManagerInterface $messageManager
     * @param OrderRepository $orderRepository
     * @param Logger $logger
     * @param OrderCollection $orderCollection
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        StoreManager $storeManager,
        DataHelper $dataHelper,
        CartManagementInterface $quoteManagement,
        ManagerInterface $messageManager,
        OrderRepository $orderRepository,
        Logger $logger,
        OrderCollection $orderCollection
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->quoteManagement = $quoteManagement;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->orderCollection = $orderCollection;
    }
    
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('transaction_token');
        $processor_transaction_id = $this->getRequest()->getParam('processor_transaction_id');
        $isWithThreeD = $this->getRequest()->getParam('threed');
        $cartUrl = $this->storeManager->getStore()->getUrl('checkout/cart');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        //@Todo additional condition if token is aleady saved (no dublicate orders)
        
        $collection = $this->orderCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('token', $transactionId);
        if ($collection->getSize() == 0) {
            $this->messageManager->addError(
                __('Order with this token was not placed.')
            );
            $this->checkoutSession->restoreQuote();
            return $this->_redirect($cartUrl);
        }
        $order = $collection->getFirstItem();

        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $paymentManager->setUrlAttributes([$transactionId]);
            $response = $paymentManager->getOrder();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            $this->messageManager->addError(
                __('SORRY! There is a problem. Please contact us.')
            );
            $this->checkoutSession->restoreQuote();
            return $this->_redirect($cartUrl);
        }

        if ($isWithThreeD != 'no') {
            if ($response->status != 'CHARGEABLE') {
                $this->messageManager->addError(
                    __('There is some issue with your credit card.')
                );
                $order->registerCancellation('There was an issue with the credit card.')->save();
                $this->checkoutSession->restoreQuote();
                return $this->_redirect($cartUrl);
            }

            try {
                $paymentManager->setUrlAttributes([$transactionId]);
                $responseConfirm = $paymentManager->confirm();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->messageManager->addError(
                    __('SORRY! There is a problem. Please contact us.')
                );
                $this->checkoutSession->restoreQuote();
                return $this->_redirect($cartUrl);
            }

            try {
                $paymentManager->setUrlAttributes([$transactionId]);
                $response = $paymentManager->getOrder();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->messageManager->addError(
                    __('SORRY! There is a problem. Please contact us.')
                );
                $this->checkoutSession->restoreQuote();
                return $this->_redirect($cartUrl);
            }
        }


        if ($response->status == 'WAIT_THREEDS') {
            try {
                $paymentManager->setUrlAttributes([$transactionId , $processor_transaction_id]);
                $responseConfirm = $paymentManager->confirm();
                if ($responseConfirm->status == 'success') {
                    // Generate the invoice here too (the DONE branch below does the same); otherwise a
                    // WAIT_THREEDS -> confirm -> success order reached the success page uninvoiced.
                    $this->dataHelper->generateInvoice($order);
                    $this->checkoutSession->setAdditionalInfo($response);
                    return $resultRedirect->setPath(
                            'checkout/onepage/success',
                            ['_current' => true]
                    );
                } else {
                    $this->logger->debug('Netpay confirm not successful for token ' . $transactionId);
                    $this->messageManager->addError(
                        __('3DS SORRY! There is a problem. Please contact us.')
                    );
                    $order->registerCancellation('3DS confirm was not successful.')->save();
                    $this->checkoutSession->restoreQuote();
                    return $this->_redirect($cartUrl);
                }
            } catch (\Exception $e) {
                // The confirm may already have been applied by the frontend confirm3DS step (a repeat
                // confirm can 409). Re-read the gateway state before giving up: if the order is already
                // paid, settle it instead of cancelling a valid order.
                $this->logger->debug($e->getMessage());
                try {
                    $paymentManager->setUrlAttributes([$transactionId]);
                    $recheck = $paymentManager->getOrder();
                    if (in_array($recheck->status, ['DONE', 'CHARGEABLE'], true)) {
                        $this->dataHelper->generateInvoice($order);
                        $this->checkoutSession->setAdditionalInfo($recheck);
                        return $resultRedirect->setPath('checkout/onepage/success', ['_current' => true]);
                    }
                } catch (\Exception $ignored) {
                    $this->logger->debug('NetPay 3DS re-check failed: ' . $ignored->getMessage());
                }
                $this->messageManager->addError(
                    __('SORRY! There is a problem. Please contact us.')
                );
                $this->checkoutSession->restoreQuote();
                return $this->_redirect($cartUrl);
            }
        }

        if ($response->status == 'success') {
            try {
                $this->checkoutSession->setAdditionalInfo($response);
                return $resultRedirect->setPath(
                        'checkout/onepage/success',
                        ['_current' => true]
                );
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->messageManager->addError(
                    __('SORRY! There is a problem. Please contact us.')
                );
                $this->checkoutSession->restoreQuote();
                return $this->_redirect($cartUrl);
            }

        } 

        if (in_array($response->status, ['DONE', 'CHARGEABLE'], true)) {

            // Generate an invoice of the order. CHARGEABLE is a valid approved state too — without it
            // a frictionless CHARGEABLE order fell into the else branch below and was wrongly cancelled.
            $this->dataHelper->generateInvoice($order);
        } else {
            $this->messageManager->addError(
                __('There is some issue with your credit card.')
            );
            $message = $this->getInfoMessageByStatus($response->status);
            $order->registerCancellation((string) $message)->save();
            $this->checkoutSession->restoreQuote();
            return $this->_redirect($cartUrl);
        }

        $this->checkoutSession->setAdditionalInfo($response);
        return $resultRedirect->setPath(
                'checkout/onepage/success',
                ['_current' => true]
            );
    }
    
    /**
     * get Message for Oder comment by Netpay status
     * 
     * @param string $status
     * @return string
     */
    public function getInfoMessageByStatus($status)
    {
        switch ($status) {
            case "review":
                $message = __("Netpay transaction has the status review!");
                break;
            case "failed":
                $message = __("Netpay transaction failed!");
                break;
            case "rejected":
                $message = __("Netpay transaction is rejected!");
                break;
            case "unsecure":
                $message = __("Netpay transaction is unsecure!");
                break;
            default:
                $message = __("Netpay transaction has an unknown status!");
        }
        
        return $message;
    }
}