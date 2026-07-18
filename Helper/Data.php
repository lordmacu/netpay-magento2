<?php

namespace Netpay\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use BusinessLayer\Netpay\PaymentManager as PaymentManager;
use Netpay\Payment\Helper\Config as ConfigHelper;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DB\TransactionFactory;
use Netpay\Payment\Logger\Logger;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /** @var CheckoutSession */
    protected $checkoutSession;
    

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var InvoiceService */
    protected $invoiceService;

    /** @var TransactionFactory */
    protected $transactionFactory;
    
    /** @var Logger */
    protected $logger;
    
    /**
     * data constructor
     *
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param Logger $logger
     */ 
    public function __construct(
        ConfigHelper $configHelper,
        Context $context,
        CheckoutSession $checkoutSession,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->checkoutSession = $checkoutSession;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
    }

    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    public function getPaymentManager()
    {
        $storeId = $this->configHelper->getStoreId();
        $backofficeParams = [
            'secret_key_test' =>  $this->configHelper->getSecretKeyTest($storeId),
            'secret_key_live' => $this->configHelper->getSecretKeyLive($storeId),
            'public_key_test' => $this->configHelper->getPublicKeyTest($storeId),
            'public_key_live' => $this->configHelper->getPublicKeyLive($storeId),
            'mode' => $this->configHelper->getPaymentMode($storeId)
        ];
        return new PaymentManager($backofficeParams);
    }
    
    /**
     * @param $order
     *
     * Captured order and generate invoice
     */
    public function generateInvoice($order)
    {
        // Idempotent: a repeated webhook (e.g. a second oxxopay.paid) must not try to
        // re-invoice an already fully-invoiced order.
        if (!$order->canInvoice()) {
            return;
        }
        //capture the payment
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->register();

        $transaction = $this->transactionFactory->create()
        ->addObject($invoice)
        ->addObject($invoice->getOrder());

        $transaction->save();
    }
    
    public function getMsiValues($total)
    {
        $promotions = array();
        try {
            $paymentManager = $this->getPaymentManager();
            $response = $paymentManager->getConfiguration();
            if ($response->promotionAmount03 > $total && $response->promotionAmount06 > $total &&
            $response->promotionAmount09 > $total && $response->promotionAmount12 > $total &&
            $response->promotionAmount18 > $total) {
                $promotionAllow = '1';
                $promotionAllowArray = array();
                $promotionAllowArray = explode(',', $promotionAllow);
                array_unshift($promotionAllowArray, 1);
                for ($i = 0; $i < count($promotionAllowArray); $i++) {
                    $index = $promotionAllowArray[$i];
                    $netPayObjeto[$index] = (int)$promotionAllowArray[$i];
                }

                return $netPayObjeto;

            } else {
                $promotionAllow = $response->promotionAllow;
                $promotionAllowArray = array();
                $promotionAllowArray = explode(',', $promotionAllow);
                array_unshift($promotionAllowArray, 1);
                for ($i = 0; $i < count($promotionAllowArray); $i++) {
                    $index = $promotionAllowArray[$i];
                    $netPayObjeto[$index] = (int)$promotionAllowArray[$i];
                }

                return $netPayObjeto;
            }
         
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return array();
        }
    }
        
    public function getConfigManager()
    {
        try {
            $paymentManager = $this->getPaymentManager();
            $response = $paymentManager->getConfiguration();
            return $response;         
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return array();
        }
    }

    /**
     * Map a raw NetPay gateway response string to a friendly, user-facing message.
     * Ported from NetPay's WooCommerce plugin (NetPayFunctions::friendly_response).
     *
     * @param string|null $response
     * @return string
     */
    public function friendlyResponse($response)
    {
        $map = [
            'Aprobada' => 'Transacción Aprobada.',
            'Configuracion Invalida Procesador Tarjetas' => 'Error de comunicación, intente de nuevo.',
            'Error' => 'Error de comunicación, intente de nuevo.',
            'Error al procesar transaccion' => 'Error de comunicación, intente de nuevo.',
            'No response from DM' => 'Error de comunicación, intente de nuevo.',
            'Respuesta Tardia' => 'Error de comunicación, intente de nuevo.',
            '3DS is not active for this store' => 'Error de comunicación.',
            '(Null)' => 'Error de configuración.',
            'Invalido Identificador de Negocio' => 'Error de configuración.',
            'La Tienda Esta Deshabilitada' => 'Error de configuración.',
            'No Existe Registro' => 'Error de configuración.',
            'Violacion Integridad Base de Datos' => 'Error de configuración.',
            'Comercio Invalido' => 'Transacción rechazada. Error de configuración.',
            'Fondos Insuficientes' => 'Transacción no exitosa, tu tarjeta cuenta con fondos insuficientes.',
            'Rechazada Fondos Insuficientes' => 'Transacción no exitosa, tu tarjeta cuenta con fondos insuficientes.',
            'ORDER_REJECTED_BY_DM' => 'Transacción rechazada. Por favor intenta con otro método de pago.',
            'Reject by DM' => 'Transacción rechazada. Por favor intenta con otro método de pago.',
            'Rejected by Decision Manager' => 'Transacción rechazada. Por favor intenta con otro método de pago.',
            'Tarjeta Restringida' => 'Transacción rechazada. Por favor intenta con otro método de pago.',
            'Retener Tarjeta' => 'Transacción rechazada. Por favor intenta con otro método de pago.',
            'Recoger Tarjeta' => 'Transacción rechazada.',
            'Tarjeta Perdida' => 'Transacción rechazada.',
            'Tarjeta Perdida, Recoger' => 'Transacción rechazada.',
            'Invalid Context' => 'Transacción rechazada.',
            'Error, se recibio un valor nulo' => 'Información incorrecta, intente de nuevo.',
            'Formato de fecha exp invalido' => 'Información incorrecta, intente de nuevo.',
            'WAIT_AND_RESEND_REQUEST' => 'Información incorrecta, intente de nuevo.',
            'ORDER_FOR_REVIEW_BY_DM' => 'Información incompleta.',
            'REQUEST_DIFFERENT_CARD' => 'Información incompleta.',
            'RESEND_THE_REQUEST_WITH_COMPLETE_INFORMATION' => 'Información incompleta.',
            'RESEND_THE_REQUEST_WITH_CORRECT_INFORMATION' => 'Fallo en autenticación.',
            'REVIEW_CUSTOMERS_ORDER' => 'Fallo en autenticación.',
            'Transaccion Rechazada' => 'Fallo en autenticación.',
            'El Password es incorrecto' => 'Fallo en autenticación.',
            'Promocion de tarjeta invalida' => 'Meses sin intereses no compatible con la tarjeta.',
            'Promocion invalida' => 'Meses sin intereses no compatible con tarjeta.',
            'Promocion no valida para el tipo de tarjeta' => 'Meses sin intereses no compatible con tarjeta.',
            'Pago Diferido No Permitido' => 'Pago Diferido No Permitido.',
            'Tarjeta Vencida' => 'Transacción rechazada, la tarjeta que estás utilizando está expirada.',
            'Tarjeta Expirada' => 'Transacción rechazada, la tarjeta que estás utilizando está expirada.',
            'Procesador No Disponible.' => 'Transacción no exitosa, no fue posible procesar tu pago, intenta más tarde.',
            'Procesador No Disponible' => 'Transacción no exitosa, no fue posible procesar tu pago, intenta más tarde.',
            'Transaccion Invalida' => 'Tarjeta no permitida, intente con otra tarjeta.',
            'User failed authentication' => 'Tarjeta no permitida, intente con otra tarjeta.',
            'Usuario o Password Invalidos' => 'Tarjeta no permitida, intente con otra tarjeta.',
            'Issuer unable to perform authentication' => 'Tarjeta no permitida, intente nuevamente.',
            'Tipo de Tarjeta No Soportada' => 'Tarjeta inválida, intente con otra tarjeta.',
            'Rechazada' => 'Transacción rechazada. Tu tarjeta es inválida.',
            'Reservado Uso Privado' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Tarjeta Invalida' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Consultar con el Emisor de la Tarjeta' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Declinada General' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Limite Retiro Frecuencia' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Llame al Emisor' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Llame Emisor' => 'Transacción rechazada. Llame Emisor.',
            'Numero de Intentos de PIN excedido' => 'Transacción rechazada. Favor de comunicarte con tu banco.',
            'Numero de Orden Existente' => 'Numero de Orden Existente.',
            'Autorizaciones Excedidas' => 'Has superado el límite de transacciones permitidas por día, llama a tu banco.',
            'Limite Excedido' => 'Has superado el monto máximo aprobado permitido por día, llama a tu banco.',
            'Reintente' => 'Transacción rechazada. Intenta nuevamente.',
            'Monto Invalido' => 'Transacción rechazada. Monto Invalido.',
            'Formato de numero invalido' => 'Transacción rechazada. Formato de numero invalido.',
            'PIN Invalido/Excedido' => 'Transacción rechazada. PIN Invalido/Excedido.',
            'Tarjeta Sin Activar' => 'Transacción rechazada. Tarjeta Sin Activar.',
        ];
        $key = (string) $response;
        $message = isset($map[$key])
            ? $map[$key]
            : 'Transacción no exitosa, no fue posible procesar tu pago, intenta más tarde.';

        return (string) __($message);
    }
}
