<?php

namespace Netpay\Payment\Model;

use DateInterval;
use DateTime;
use DateTimeZone;
use Magento\Framework\DataObject as DataObject;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Netpay\Payment\Logger\Logger;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\OrderRepository;
use \Magento\Framework\App\Response\RedirectInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer as Observer;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Netpay\Payment\Model\CustomerLinkRepository;
use Netpay\Payment\Model\CustomerLink;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Netpay\Payment\Model\CustomConfigProvider;
use Magento\Framework\App\Action\Context as Context;
use Magento\Sales\Model\ResourceModel\Sale\Collection as Salesorder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class ChargesApiManagement implements \Netpay\Payment\Api\ChargesApiManagementInterface
{

    const ccTypes = [
        'visa' => 'VI',
        'mastercard' => 'MC',
        'amex' => 'AE',
        'discover' => 'DI',
        'jcb' => 'JCB',
        'maestrointernational' => 'MI'
    ];

    /** @var DataHelper */
    protected $dataHelper;

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var Context */
    protected $context;

    /** @var Salesorder */
    protected $salesorder;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Logger */
    protected $logger;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CheckoutSession */
    protected $checkoutSession;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var Observer */
    protected $observer;

    /** @var PaymentTokenFactoryInterface */
    protected $paymentTokenFactory;

    /** @var PaymentTokenManagementInterface */
    protected $paymentTokenManagement;

    /** @var OrderPaymentInterface */
    protected $orderPayment;

    /** @var OrderManagementInterface */
    protected $orderManagement;

    /** @var EncryptorInterface */
    protected $encryptor;

    /** @var CustomerLinkRepository */
    private $customerLinkRepository;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var AddressRepositoryInterface */
    protected $addressRepository;

    /** @var CustomerLink */
    protected $customerLink;

    /** @var MessageManager */
    protected $messageManager;

    /** @var CustomConfigProvider */
    protected $customConfigProvider;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var RemoteAddress */
    private $remoteAddress;

    /**
     * @param DataHelper $dataHelper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param OrderRepository $orderRepository
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param CustomerLinkRepository $customerLinkRepository
     */
    public function __construct(
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        StoreManagerInterface $storeManager,
        OrderPaymentInterface $orderPayment,
        OrderManagementInterface $orderManagement,
        Logger $logger,
        OrderRepository $orderRepository,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Observer $observer,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor,
        CustomerLinkRepository $customerLinkRepository,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        CustomerLink $customerLink,
        MessageManager $messageManager,
        Context $context,
        Salesorder $salesorder,
        CustomConfigProvider $customConfigProvider,
        OrderCollectionFactory $orderCollectionFactory,
        RemoteAddress $remoteAddress
    ) {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->orderPayment = $orderPayment;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->observer = $observer;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->encryptor = $encryptor;
        $this->customerLinkRepository = $customerLinkRepository;
        $this->customerLink = $customerLink;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->messageManager = $messageManager;
        $this->context = $context;
        $this->salesorder = $salesorder;
        $this->orderManagement = $orderManagement;
        $this->customConfigProvider = $customConfigProvider;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * call the charges API and return token and if necessary 3D URL
     * 
     * @param $referenceID
     * @param int $orderId
     * @param string $paymentmethod
     * @param string $token
     * @param $deviceInformation
     * @param int $msicount
     * @param bool $saveCc
     * @param string $cvv
     * @param bool $cardSelected
     * 
     * @return string
     */
    public function getCharges($referenceID, $orderId, $paymentmethod, $token, $deviceInformation,  $msicount = null, $saveCc = false, $cvv = '', $cardSelected = false)
    {
        if ($paymentmethod == 'savecc') {
            $order = (int) $this->checkoutSession->getData('last_order_id');
            if (!$order) {
                return;
            }
            $order2 = $this->orderRepository->get($order);
            $customer = $this->customerSession->getCustomer()->getId();
            $this->saveSecondCard($token, $customer, $cvv, $order2);
            return;
        }
        if ($paymentmethod == 'deletecc') {
            $customer = $this->customerSession->getCustomer()->getId();
            $this->deleteToken($token, $customer);
            return;
        }
        if ($paymentmethod == '3dsConfirm') {
            return $this->confirm3DS($token , $cvv);
        }
        if (empty($orderId)) {
            $message = __('No Order was created.');
            $this->messageManager->addErrorMessage($message);
            return $this->storeManager->getStore()->getUrl('checkout/cart');
        }
        $order = $this->orderRepository->get($orderId);
        // Multi-store: make the charge use the order's own store config (keys/mode/host),
        // not whatever ambient store the REST call resolved to.
        $this->storeManager->setCurrentStore($order->getStoreId());
        $others = new DataObject();
        $others->instegrationsdk = 'Magento';
        $others->integrationSdkVersion = '2.4.6';
        if ($paymentmethod == 'card') {
            $others->source = $token;
            $others->referenceID = $referenceID;

            $deviceArray = json_decode($deviceInformation);
            $others->deviceInformation = $deviceArray;

            // Anti-fraud: send the shopper's client IP (matches NetPay's WooCommerce plugin,
            // a signal the Decision Manager uses).
            $clientIp = $this->remoteAddress->getRemoteAddress() ?: '0.0.0.0';
            $others->zoneAware = (object) ['clientIPAdress' => (string) $clientIp];

            if ($cardSelected) {
                $saveCc = false;
            }
            $others->saveCard = $saveCc;
            $others->merchantRedirectUrl = $this->storeManager->getStore()->getUrl("netpay/payment/reside");

            if (!empty($msicount) && $msicount > 1) {
                $others->msicount = $msicount;
                $others->msiinterval = "month";
            }
            if ($saveCc && $order->getCustomerId()) {
                $customer = $this->customerRepository->getById($order->getCustomerId());
                list($clientid, $setSaveCc) = $this->getClientId($customer, $token, $saveCc, $cardSelected);
                if (!$setSaveCc) {
                    unset($others->saveCard);
                } else {
                    $others->saveCard = false;
                }
            } else if (!$saveCc && $cardSelected && $order->getCustomerId()) {
                $customer = $this->customerRepository->getById($order->getCustomerId());
                list($clientid, $setSaveCc) = $this->getClientId($customer, $token, $saveCc, $cardSelected);
                $others->clientid = $clientid;
                $others->cvv = $cvv;
            }
            
        } else {
            $others->merchantRedirectUrl = null;
        }
        $others->paymentmethod = $paymentmethod;
        $others->description = (string) __('Cobro de la orden %1', $order->getIncrementId());
        try {
            $paymentManager = $this->dataHelper->getPaymentManager();
            $others->saveCard = $saveCc;
            $others->referenceID = $referenceID;
            $paymentManager->setShopdata($order, $others);
            $charges = $paymentManager->getToken();
            if ($saveCc && $paymentmethod == 'card') {
                $cardDetails = $charges->paymentSource->card;
                $payment = $order->getPayment();
                $extensionAttributes = $payment->getExtensionAttributes();
                $paymentToken = $this->paymentTokenFactory->create('card');
                $paymentToken->setGatewayToken($token);
                $expDate = new DateTime(
                    $cardDetails->expYear
                        . '-'
                        . $cardDetails->expMonth
                        . '-'
                        . '01'
                        . ' '
                        . '00:00:00',
                    new DateTimeZone('UTC')
                );
                $expDate->add(new DateInterval('P1M'));
                $expiryDate = $expDate->format('Y-m-d 00:00:00');
                $paymentToken->setExpiresAt($expiryDate);
                $paymentToken->setTokenDetails(json_encode([
                    'type' => $this->getCreditCardType($cardDetails->brand),
                    'maskedCC' => $cardDetails->lastFourDigits,
                    'expirationDate' => $cardDetails->expMonth . '/' . $cardDetails->expYear
                ]));
                $paymentToken->setPaymentMethodCode($order->getPayment()->getMethod());
                $paymentToken->setCustomerId($order->getCustomerId());
                $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
                $alreadyExistPaymentToken = $this->paymentTokenManagement->getByPublicHash($paymentToken->getPublicHash(), $paymentToken->getCustomerId());
                if ($alreadyExistPaymentToken != null && $alreadyExistPaymentToken->getData()) {
                    $alreadyExistPaymentToken->setIsActive(1)->setIsVisible(1)->save();
                } else {
                    $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $payment);
                    $extensionAttributes->setVaultPaymentToken($paymentToken);
                }
            }
            if($paymentmethod === 'oxxopay'){
                $order->setToken($charges->paymentSource->oxxoPay->reference);
                $order->addCommentToStatusHistory('OxxoPay Transaction ID: ' . $charges->paymentSource->oxxoPay->reference);
            }else{
                $order->setToken($charges->transactionTokenId);
                $order->addCommentToStatusHistory('Netpay Transaction ID: ' . $charges->transactionTokenId);
            }
            $order->save();
        } catch (\Exception $ex) {
            $this->logger->debug($ex->getMessage());
            $order->registerCancellation('There was an issue in Netpay. Please contact us.')->save();
            $this->checkoutSession->restoreQuote();
            $message = __('An error occurred on the server. Please try to place the order again.');
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__('Ha ocurrido un error, por favor. Intente realizar el pedido de nuevo'));
        }
        if ($paymentmethod == 'card') {
            if ($charges->status == "success") {
                $varAccept = new DataObject();
                $status = 'success';
                $url = $others->merchantRedirectUrl . '?transaction_token=' . $charges->transactionTokenId . '&threed=no';
                $varAccept->status = $status;
                $varAccept->url = $url;
                return json_encode($varAccept);
            } elseif ($charges->status == "review" && !empty($charges->returnUrl)) {
                return json_encode($charges);
            }
        } else {
            if ($charges->status == "success") {
                $this->checkoutSession->setAdditionalInfo($charges);
                return $this->storeManager->getStore()->getUrl('checkout/onepage/success');
            } else {
                $this->logger->debug('Netpay cash order not successful, status: ' . ($charges->status ?? 'unknown'));
                $order->registerCancellation('There was an issue in Netpay. Please contact us.')->save();
                $this->checkoutSession->restoreQuote();
                $message = __('Placing Order in Netpay wasnt successful.');
                $this->messageManager->addErrorMessage($message);
                return $this->storeManager->getStore()->getUrl('checkout/cart', array('_secure' => true));
            }
        }
        return false;
    }

    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param $type
     * @return string
     */
    private function getCreditCardType($type): string
    {
        if (isset(self::ccTypes[$type]) && !empty(self::ccTypes[$type])) {
            return self::ccTypes[$type];
        }

        return $type;
    }

    /**
     * check if customer has already a netpay id. If not call Client API and 
     * and save this id in netpay_customer
     * 
     * @param int $customerId
     * @return string $clientId
     */
    private function getClientId($customer, $token, $saveCc, $cardSelected)
    {
        $customerLink = $this->customerLinkRepository->get($customer->getId());
        if ($customer->getId()) {
            $others = new DataObject();
            $others->source = $token;
            $others->type = 'card';
            $others->clientid = $customerLink->getNetpayId();
            $others->firstname = $customer->getFirstname();
            $others->lastname = $customer->getLastname();
            $billingAddressId = $customer->getDefaultBilling();
            $shippingAddressId = $customer->getDefaultShipping();
            $billingAddress = $this->addressRepository->getById($billingAddressId);
            $telephone = $billingAddress->getTelephone();
            if ($telephone) {
                $others->phone = $telephone;
            } else {
                $others->phone = '-';
            }
            $others->email = $customer->getEmail();
            $others->identifier = $customer->getId();

            try {
                $paymentManager = $this->dataHelper->getPaymentManager();
                $paymentManager->setShopdata(null, $others);
                if (empty($customerLink->getNetpayId())) {
                    $client = $paymentManager->setClient();
                    $customerLink = $this->customerLink;
                    $customerLink->setNetpayId($client->id)
                        ->setCustomerId($customer->getId())
                        ->setStoreId((int) $this->storeManager->getStore()->getId());
                    $this->customerLinkRepository->save($customerLink);
                    return [$client->id, true];
                }
                if (!$saveCc && $cardSelected) {
                    $customerLink->getNetpayId();
                    return [$customerLink->getNetpayId(), false];
                }
            } catch (\Exception $ex) {
                $this->logger->debug($ex->getMessage());
            }

            // Fallback: returning customer that already has a NetPay client id, or a failed lookup.
            // Always return a 2-element array so the list() destructuring in the caller is safe.
            return [$customerLink->getNetpayId(), false];
        }

        return [null, false];
    }

    /**
     * Metodo para guardado de una segunda tarjeta
     * @param $token
     * @param $customer
     * @param $cvv
     * @param $order
     * @return
     */
    public function saveSecondCard($token, $customer, $cvv, $order)
    {
        $customerLink = $this->customerLinkRepository->get($customer);
        $prueba = $customerLink->getNetpayId();
        $others = new DataObject();
        $others->token = $token;
        // preAuth is intentionally not sent — NetPay's own WooCommerce plugin does not send it
        // when saving a card, so we let NetPay apply its default (no pre-authorization).
        $others->cvv2 = $cvv;
        $paymentManager = $this->dataHelper->getPaymentManager();
        $paymentManager->setShopdata(null, $others);
        $paymentManager->setUrlAttributes([$customerLink->getNetpayId()]);
        $updateClient = $paymentManager->updateClient();

        $payment = $order->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $this->paymentTokenFactory->create('card');
        $paymentToken->setGatewayToken($token);
        $expDate = new DateTime(
            $updateClient->expYear
                . '-'
                . $updateClient->expMonth
                . '-'
                . '01'
                . ' '
                . '00:00:00',
            new DateTimeZone('UTC')
        );
        $expDate->add(new DateInterval('P1M'));
        $expiryDate = $expDate->format('Y-m-d 00:00:00');
        $paymentToken->setExpiresAt($expiryDate);
        $paymentToken->setTokenDetails(json_encode([
            'type' => $this->getCreditCardType($updateClient->brand),
            'maskedCC' => $updateClient->lastFourDigits,
            'expirationDate' => $updateClient->expMonth . '/' . $updateClient->expYear
        ]));

        $paymentToken->setPaymentMethodCode('netpay');
        $paymentToken->setCustomerId($customer);
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $alreadyExistPaymentToken = $this->paymentTokenManagement->getByPublicHash($paymentToken->getPublicHash(), $paymentToken->getCustomerId());

        if ($alreadyExistPaymentToken != null && $alreadyExistPaymentToken->getData()) {
            $alreadyExistPaymentToken->setIsActive(1)->setIsVisible(1)->save();
        } else {
            $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Metodo de eliminacion de una tarjeta ya guardada
     * @param string $token
     * @param int $customer
     * @return string
     */
    public function deleteToken($token, $customer)
    {
        $customerLink = $this->customerLinkRepository->get($customer);
        $paymentManager = $this->dataHelper->getPaymentManager();
        $paymentManager->setUrlAttributes([$customerLink->getNetpayId(), $token]);
        $deleteClient = $paymentManager->deleteClient();
    }

    /**
     * Metodo de confirm de transaccion despues de modal 3ds
     * @param string $transactionId
     * @param int $processor_transaction_id
     * @return
     */
    public function confirm3DS ($transactionId, $processor_transaction_id) {
        // WooCommerce-parity 3DS contract. A charge in "review" is NOT necessarily confirmable:
        // the Decision Manager (anti-fraud) may already have marked it FAILED, or it may be
        // waiting for the 3DS step-up. On the frictionless branch (no processorTransactionId) we
        // therefore read the real transaction state BEFORE confirming, instead of confirming
        // blindly (which turns a DM failure into an opaque HTTP 409).
        $isFrictionless = ($processor_transaction_id === null
            || $processor_transaction_id === ''
            || $processor_transaction_id === 'null');

        if ($isFrictionless) {
            $state = $this->getTransactionState($transactionId);
            $status = strtoupper((string)($state->status ?? ''));

            if ($status === 'FAILED' || $status === 'REJECTED' || $status === 'REJECT') {
                // Fraud/gateway rejection (e.g. Decision Manager). Cancel the still-pending order so
                // it is not orphaned, and return a friendly reason instead of the raw gateway string.
                $rawMessage = $state->responseMsg ?? $status;
                $this->cancelOrderByToken($transactionId, (string) $rawMessage);
                return json_encode([
                    'status' => 'failed',
                    'transactionTokenId' => $transactionId,
                    'responseCode' => $state->responseCode ?? null,
                    'responseMsg' => (string) $this->dataHelper->friendlyResponse($rawMessage),
                ]);
            }

            if ($status === 'DONE' || $status === 'CHARGEABLE') {
                // Already approved by the gateway; no confirm needed.
                $reside = $this->storeManager->getStore()->getUrl('netpay/payment/reside');
                return json_encode([
                    'status' => 'success',
                    'transactionTokenId' => $transactionId,
                    'authCode' => $state->authCode ?? null,
                    'urlConfirmBefore3ds' => $reside . '?transaction_token=' . $transactionId . '&threed=no',
                ]);
            }
            // WAIT_THREEDS (or unknown) falls through to confirm below.
        }

        // Confirm. Frictionless sends the LITERAL "null" as processorTransactionId (NetPay's own
        // contract), a challenge sends the real processorTransactionId from Cardinal.
        try {
            $confirmId = $isFrictionless ? 'null' : $processor_transaction_id;
            $paymentManager = $this->dataHelper->getPaymentManager();
            $paymentManager->setUrlAttributes([$transactionId, $confirmId]);
            $responseConfirm = $paymentManager->confirm();
        } catch (\Exception $e) {
            // A confirm that the gateway rejects (e.g. HTTP 409) must not bubble up as a raw
            // exception; return a clean failed state so the frontend can show the error.
            $this->logger->debug('NetPay 3DS confirm failed: ' . $e->getMessage());
            return json_encode([
                'status' => 'failed',
                'transactionTokenId' => $transactionId,
                'responseMsg' => $e->getMessage(),
            ]);
        }

        $url = $responseConfirm->redirect3dsUri . '?transaction_token=' . $transactionId . '&threed=no';
        $responseConfirm->urlConfirmBefore3ds = $url;
        return json_encode($responseConfirm);
    }

    /**
     * Read the current transaction state from the gateway (GET /v3/transactions/{id}).
     * Used by the 3DS confirm flow to branch on WAIT_THREEDS / FAILED / DONE / CHARGEABLE.
     *
     * @param string $transactionId
     * @return \stdClass
     */
    private function getTransactionState($transactionId)
    {
        $paymentManager = $this->dataHelper->getPaymentManager();
        $paymentManager->setUrlAttributes([$transactionId]);

        return $paymentManager->getOrder();
    }

    /**
     * Cancel a still-pending order identified by its NetPay transaction token, so a failed 3DS or
     * fraud-rejected charge does not leave an orphaned pending order behind.
     *
     * @param string $token
     * @param string $reason
     * @return void
     */
    private function cancelOrderByToken($token, $reason)
    {
        try {
            $order = $this->orderCollectionFactory->create()
                ->addFieldToFilter('token', $token)
                ->setPageSize(1)
                ->getFirstItem();
            if ($order->getId() && $order->canCancel()) {
                $order->registerCancellation('NetPay: ' . $reason)->save();
            }
        } catch (\Exception $e) {
            $this->logger->debug('NetPay cancelOrderByToken failed: ' . $e->getMessage());
        }
    }
}