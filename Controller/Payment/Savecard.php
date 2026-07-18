<?php

namespace Netpay\Payment\Controller\Payment;

use DateTime;
use DateTimeZone;
use DateInterval;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Customer\Model\Session;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Api\CustomerLinkRepositoryInterface;
use Magento\Framework\DataObject as DataObject;
use \Magento\Framework\Message\ManagerInterface;
use Netpay\Payment\Logger\Logger;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class Savecard
 *
 */
class Savecard extends Action
{   
    /** @var CreditCardTokenFactory */
    protected $creditCardTokenFactory;

    /** @var Session */
    protected $customSession;

    /** @var DataHelper */
    protected $paymentTokenRepository;

    /** @var EncryptorInterface */
    protected $encryptor;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var CustomerLinkRepositoryInterface */
    protected $customerLinkRepository;
    
    /** @var ManagerInterface */
    protected $messageManager;
    
    /** @var Logger */
    protected $logger;

    protected $paymentTokenManagement;
    
    public function __construct(
        Context $context,
        CreditCardTokenFactory $creditCardTokenFactory,
        Session $customSession,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor,
        DataHelper $dataHelper,
        CustomerLinkRepositoryInterface $customerLinkRepository,
        ManagerInterface $messageManager,
        Logger $logger,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct($context);
        $this->creditCardTokenFactory = $creditCardTokenFactory;
        $this->customSession = $customSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->dataHelper = $dataHelper;
        $this->customerLinkRepository = $customerLinkRepository;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if ($params) {
            foreach (['token', 'cvv', 'expiry_year', 'expiry_month', 'card_type', 'cardNumber'] as $required) {
                if (!isset($params[$required]) || $params[$required] === '') {
                    $this->messageManager->addError(__('Missing card data. Please try again.'));
                    return $this->resultRedirectFactory->create()->setPath('customer/account');
                }
            }
            $customerId = $this->customSession->getCustomer()->getId();
            $customerLink = $this->customerLinkRepository->get($customerId);
            if (!empty($customerLink->getData())) {
                try {
                    $others = new DataObject();
                    $others->clientId = $customerLink->getNetpayId();
                    $others->token = $params['token']; 
                    $others->preAuth = true;
                    $others->cvv2 = $params['cvv'];
                    $paymentManager = $this->dataHelper->getPaymentManager();
                    $paymentManager->setShopdata(null, $others);
                    $paymentManager->setUrlAttributes([$customerLink->getNetpayId()]);
                    $updateClient = $paymentManager->updateClient();
                } catch (\Exception $ex) {
                    $this->logger->debug($ex->getMessage());
                    $this->messageManager->addError(__("There is some problem while saving your card on Netpay!!"));
                    return $this->resultRedirectFactory->create()->setPath('customer/account');
                }
            }
            $paymentToken = $this->creditCardTokenFactory->create();
            $expDate = new DateTime(
                $params['expiry_year']
                . '-'
                . $params['expiry_month']
                . '-'
                . '01'
                . ' '
                . '00:00:00',
                new DateTimeZone('UTC')
            );
            $expDate->add(new DateInterval('P1M'));
            $expiryDate = $expDate->format('Y-m-d 00:00:00');
            $paymentToken->setExpiresAt($expiryDate);
            $paymentToken->setGatewayToken($params['token']);
            
            $ccNum = $params['cardNumber'];
            $last4Digits    = preg_replace( "#(.*?)(\d{4})$#", "$2", $ccNum);
            $paymentToken->setTokenDetails(json_encode([
                'type'              => $params['card_type'],
                'maskedCC'          => $last4Digits,
                'expirationDate'    => $params['expiry_month'].'/'.$params['expiry_year']
            ]));
            $paymentToken->setIsActive(true);
            $paymentToken->setIsVisible(true);
            $paymentToken->setPaymentMethodCode('netpay');
            $paymentToken->setCustomerId($customerId);
            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
            $alreadyExistPaymentToken = $this->paymentTokenManagement->getByPublicHash( $paymentToken->getPublicHash(), $paymentToken->getCustomerId() );
            if ($alreadyExistPaymentToken !=null && $alreadyExistPaymentToken->getData()) {
                $alreadyExistPaymentToken->setIsActive(1)->setIsVisible(1)->save();
            } else {
                $this->paymentTokenRepository->save($paymentToken);
            }
            $this->messageManager->addSuccess(__("Netpay card saved successfully"));
        }

        return $this->resultRedirectFactory->create()->setPath('customer/account');
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
}