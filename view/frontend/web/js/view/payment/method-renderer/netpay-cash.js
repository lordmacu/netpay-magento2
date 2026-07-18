define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'jquery',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component, quote, additionalValidators, urlBuilder, storage, $, errorProcessor, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Netpay_Payment/payment/netpay-cash'
            },
            isActive : function() {
                return window.checkoutConfig.payment.netpaycash.active;
            }, 
            getDescription : function() {
                return window.checkoutConfig.payment.netpaycash.description;
            },
            getMode: function () {
                return window.checkoutConfig.payment.netpay.mode === 'test' ? true : false;
            },
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate()) {
                        self.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function (orderId) {
                                var chargeUrl = urlBuilder.createUrl('/payment/charges', {});
                                var customPayload = {
                                    orderId: orderId,
                                    deviceInformation: '',
                                    referenceID: 1,
                                    token: '',
                                    paymentmethod: 'oxxopay'
                                };
                                fullScreenLoader.startLoader();
                                storage.post(
                                    chargeUrl, 
                                    JSON.stringify(customPayload),
                                    true
                                ).done(
                                    function (response) {
                                        console.log('success');
                                        window.location.href = response;
                                    }
                                ).fail(
                                    function (response) {
                                        console.log('fail');
                                        errorProcessor.process(response);
                                    }
                                ).always(fullScreenLoader.stopLoader);
                            }
                        );
                    return true;
                }
                return false;
            },       
        });
    }
);