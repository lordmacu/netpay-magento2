<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netpay\Payment\Controller\Cards;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Controller\CardsManagement;
use Magento\Vault\Model\PaymentTokenManagement;
use Netpay\Payment\Helper\Data as DataHelper;
use Netpay\Payment\Api\CustomerLinkRepositoryInterface;
use Magento\Framework\DataObject as DataObject;
use Netpay\Payment\Logger\Logger;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteAction extends \Magento\Vault\Controller\Cards\DeleteAction
{
    const TOO_LESS_CC = 4;

    /** @var array */
    protected $errorsMap = [];

    /** @var Validator */
    protected $fkValidator;

    /** @var PaymentTokenRepositoryInterface */
    protected $tokenRepository;

    /** @var PaymentTokenManagement */
    protected $paymentTokenManagement;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var CustomerLinkRepositoryInterface */
    private $customerLinkRepository;

    /** @var Logger */
    protected $logger;

    public function __construct(
        Context $context,
        Session $customerSession,
        JsonFactory $jsonFactory,
        Validator $fkValidator,
        PaymentTokenRepositoryInterface $tokenRepository,
        PaymentTokenManagement $paymentTokenManagement,
        DataHelper $dataHelper,
        CustomerLinkRepositoryInterface $customerLinkRepository,
        Logger $logger
    ) {
        parent::__construct($context, $customerSession, $jsonFactory, $fkValidator, $tokenRepository, $paymentTokenManagement);
        $this->fkValidator = $fkValidator;
        $this->tokenRepository = $tokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->dataHelper = $dataHelper;
        $this->customerLinkRepository = $customerLinkRepository;
        $this->logger = $logger;
        
        $this->errorsMap = [
            self::WRONG_TOKEN => __('No token found.'),
            self::WRONG_REQUEST => __('Wrong request.'),
            self::ACTION_EXCEPTION => __('Deletion failure. Please try again.'),
            self::TOO_LESS_CC => __('You can not delete this Credit Card. Please create at first one and then try to delete it.'),
        ];
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $request = $this->_request;
        if (!$request instanceof Http) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        if (!$this->fkValidator->validate($request)) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        $paymentToken = $this->getPaymentToken($request);
        if ($paymentToken === null) {
            return $this->createErrorResponse(self::WRONG_TOKEN);
        }
        
        $customerId = $this->customerSession->getCustomer()->getId();
        $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);
        $cards = 0;
        foreach($cardList as $card) {
            if ($card->getPaymentMethodCode() == 'netpay' && $card->getIsActive()) {
                $cards++;
            }
        }
        if ($cards <= 1) {
            return $this->createErrorResponse(self::TOO_LESS_CC);
        }

        try {
            try {
                $customerLink = $this->customerLinkRepository->get($customerId);
                $others = new DataObject();
                $clientid = $customerLink->getNetpayId();
                $token = $paymentToken->getGatewayToken(); 
                $paymentManager = $this->dataHelper->getPaymentManager();
                $paymentManager->setUrlAttributes(array($clientid, $token));
                $deleteClient = $paymentManager->deleteClient();
            } catch (\Exception $ex) {
                $this->logger->debug($ex->getMessage());
                return $this->createErrorResponse(self::ACTION_EXCEPTION);
            }
            $this->tokenRepository->delete($paymentToken);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return $this->createErrorResponse(self::ACTION_EXCEPTION);
        }

        return $this->createSuccessMessage();
    }
        /**
     * @param int $errorCode
     * @return ResponseInterface
     */
    private function createErrorResponse($errorCode)
    {
        $this->messageManager->addErrorMessage(
            $this->errorsMap[$errorCode]
        );

        return $this->_redirect('vault/cards/listaction');
    }

    /**
     * @return ResponseInterface
     */
    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Stored Payment Method was successfully removed')
        );
        return $this->_redirect('vault/cards/listaction');
    }

    /**
     * @param Http $request
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken(Http $request)
    {
        $publicHash = $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH);

        if ($publicHash === null) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash(
            $publicHash,
            $this->customerSession->getCustomerId()
        );
    }
}
