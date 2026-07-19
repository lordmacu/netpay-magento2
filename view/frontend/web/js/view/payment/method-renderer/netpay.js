define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'jquery',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/model/customer',
        'Netpay_Payment/js/device-fingerprint',
    ],
    function (Component, additionalValidators, urlBuilder, storage, $, errorProcessor, fullScreenLoader, customer, doProfile) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Netpay_Payment/payment/netpay',
                netpayJsLoaded: false,
                isCreditCardSelected: false
            },
            isActive : function() {
                return window.checkoutConfig.payment.netpay.active;
            },  
            getDescription : function() {
                return window.checkoutConfig.payment.netpay.description;
            },
            getMsiValues: function() {
                var msiValues = window.checkoutConfig.payment.netpay.msiValues;
                return _.map(msiValues, function(value, key) {
                    if (key == '1') {
                        return {
                            'value': key,
                            'type': 'Pago en una sola exhibición'
                        };
                    } else {
                        return {
                            'value': key,
                            'type': key + ' meses sin intereses'
                        };
                    }    
                });
            },
            showMsi: function() {
                var msiShowValues = window.checkoutConfig.payment.netpay.msiValues;
                var msiShowValues = Object.keys(msiShowValues).length;
                if (msiShowValues > 0) {
                    return true;
                }
                return false;
            },
            getCcValues: function() {
                var ccValues = window.checkoutConfig.payment.netpay.ccValues;
                return _.map(ccValues, function(value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },
            getSavingCondition: function() {
                return [
                    {'value': 0, 'type': 'No'},
                    {'value': 1, 'type': 'Yes'}
                ];
            },
            showSaveCc: function() {
                return customer.isLoggedIn();
            },
            showCVV: function() {
                if (window.checkoutConfig.payment.netpay.ccAvailable == 1) {
                    return true;
                }
                return false;
            },
            callback: function(_this, referenceID) {
                window.referenceID = referenceID;
            },
            getMode: function () {
                return window.checkoutConfig.payment.netpay.mode === 'test' ? true : false;
            },
            // ThreatMetrix org id used for device fingerprinting (matches NetPay's WooCommerce plugin).
            getOrgId: function () {
                return window.checkoutConfig.payment.netpay.mode === 'live' ? '9ozphlqx' : '45ssiuz3';
            },
            // Retry-safe: drop sticky Cardinal/Songbird 3DS state so a new attempt does not inherit
            // stale challenge data from a previous failed try (matches the WooCommerce plugin).
            clearThreeDsStorage: function () {
                try {
                    var reKey = /(cardinal|songbird|cca|3ds)/i;
                    ['localStorage', 'sessionStorage'].forEach(function (storeName) {
                        var store = window[storeName];
                        if (!store) {
                            return;
                        }
                        for (var i = store.length - 1; i >= 0; i--) {
                            var k = store.key(i);
                            if (k && reKey.test(k)) {
                                store.removeItem(k);
                            }
                        }
                    });
                } catch (e) {
                    // storage may be unavailable (private mode); ignore.
                }
            },
            initObservable: function() {
                var self = this._super();
                // Fire the ThreatMetrix device fingerprint once (matches the WooCommerce plugin).
                // The generated session id is sent on the charge as deviceFingerPrint/sessionId.
                if (!window.netpayDeviceFingerprint) {
                    window.netpayDeviceFingerprint = doProfile(self.getOrgId());
                }
                if(!self.netpayJsLoaded) {
                    $.getScript('https://cdn.netpay.mx/js/latest/netpay3ds-noConflict.js', function() {
                        let _this = this;
                        netpay3ds.setSandboxMode(self.getMode());
                        netpay3ds.init(function () {
                            netpay3ds.config(_this, window.checkoutConfig.totalsData.grand_total, self.callback);
                        });
                    });
                    $.getScript("https://docs.netpay.mx/cdn/v1.3/netpay.min.js", function() {
                        self.netpayJsLoaded = true;
                        var configManager = window.checkoutConfig.payment.netpay.configManager;
                        if(configManager.creditCardEnable == "0" && configManager.debitCardEnable == "0" && configManager.promotionEnable == "0"){
                            $("#netpay-card-form").hide();
                            if (configManager.oxxoPay.ennable == "0"){
                                $("#netpay-cash-form").hide();
                            }
                        } else if (configManager.oxxoPay.ennable == "0"){
                            $("#netpay-cash-form").hide();
                        }
                        if(configManager.creditCardEnable == "0" && configManager.debitCardEnable == "0" && configManager.promotionEnable == "1"){
                            var promotionNumers = configManager.promotionAllow.split(',');
                            var jsonData = {
                                "promotionAmount3": configManager.promotionAmount03,
                                "promotionAmount6": configManager.promotionAmount06,
                                "promotionAmount9": configManager.promotionAmount09,
                                "promotionAmount12": configManager.promotionAmount12,
                                "promotionAmount18": configManager.promotionAmount18}
                            var jsonData2 = []
                            for (let i = 0; i < promotionNumers.length; i++) {
                                var nameProp  = `promotionAmount${promotionNumers[i]}`;
                                jsonData2.push(jsonData[nameProp])
                            }
                            var numericValues = Object. values(jsonData2).map(Number);
                            var minValue = Math.min(...numericValues);
                            if(window.checkoutConfig.totalsData.grand_total < minValue){
                                $("#netpay-card-form").hide();
                            }
                        }

                        const selectSave = document.getElementById("netpay_payment_cc_id")
                        const selectCc = $("#netpay_payment_cc_id");
                        const cantidadOpciones = selectCc.find("option").length;
                        const btnDeleteCard = document.getElementById("deleteBtn");
                        let tokenCC = '';
                        $("#deleteBtn").hide();
                        selectSave?.addEventListener("change", function(event) {
                            event.preventDefault();
                            btnDeleteCard.disabled = cantidadOpciones == 2 ? true : false;
                            var selectedOption = selectSave.options[selectSave.selectedIndex].value;
                            if(selectedOption != ''){
                                $("#deleteBtn").show();
                                tokenCC = selectedOption;
                            }else{
                                $("#deleteBtn").hide();
                            }
                        });

                        btnDeleteCard?.addEventListener("click", function(event) {
                            event.preventDefault();
                            fullScreenLoader.startLoader();
                            deleteCardMethod(tokenCC);
                        });

                        function deleteCardMethod(tokenCC){
                            var chargeUrl = urlBuilder.createUrl('/payment/charges', {});
                            var customPayload = {
                                referenceID: window.referenceID,
                                orderId: '1',
                                token: tokenCC,
                                deviceInformation: '',
                                paymentmethod: 'deletecc',
                                msicount: '0', 
                                cvv: '111',
                                saveCc: false,
                                cardselected: false,
                            };
                            storage.post(
                                chargeUrl, 
                                JSON.stringify(customPayload),
                                true
                            ).done(
                                function (response) {
                                    fullScreenLoader.startLoader();
                                    console.log('Card delete successfully');
                                    const npPlaceorder = document.getElementById("netpay_placeorder");
                                    npPlaceorder.disabled = true;
                                    btnDeleteCard.disabled = true;
                                    window.location.reload();
                                }
                            ).fail(
                                function (response) {
                                    errorProcessor.process(response);
                                    console.log('Error deleting card');
                                    window.location.reload();
                                }
                            ).always(fullScreenLoader.stopLoader);
                        }

                        if(cantidadOpciones > 1 && cantidadOpciones < 4){
                            $("#fieldsetSavecc").hide();
                            $("#fieldsetCheck").show()
                        }else if(cantidadOpciones >= 4){
                            $("#fieldsetSavecc").hide();
                            $("#fieldsetCheck").hide()
                        }else{
                            $("#fieldsetSavecc").show();
                            $("#fieldsetCheck").hide()
                        }

                        const mostrarDivCheckbox = $("#saveccCheck");
                        const miDiv = $("#containerCreditCard");
                        miDiv.hide();
                        const cardNumber = document.getElementById("cardNumber");
                        cardNumber?.addEventListener('input', function (e) {
                            e.preventDefault();
                            const valor = cardNumber.value.replace(/\D/g, '');
                            let formateado = '';
                            for (let i = 0; i < valor.length; i += 4) {
                                formateado += valor.slice(i, i + 4);
                                if (i + 4 < valor.length) {
                                    formateado += '-';
                                }
                            }
                            cardNumber.value = formateado;
                        });

                        const expirationDate = document.getElementById("expirationDate");
                        expirationDate?.addEventListener('input', function (e) {
                            e.preventDefault();
                            var chars = expirationDate.value.replace(/\D/g, "").match(/.{1,2}/g);
                            let expDateMonthValue;
                            let expDateYearValue;
                            if (chars) {
                                expirationDate.value = chars.join("/");
                                if (chars.length == 2) {
                                    expDateMonthValue = chars[0];
                                    expDateYearValue = chars[1];
                                } else {
                                    expDateMonthValue = null;
                                    expDateYearValue = null;
                                }
                            }
                        });

                        mostrarDivCheckbox.on('change', function() {
                            mostrarDivCheckbox[0].checked ? miDiv.show() : miDiv.hide();
                        });

                        const btnSaveCard = document.getElementById("btnSaveCard");
                        btnSaveCard?.addEventListener("click", function(event) {
                            event.preventDefault();
                            btnSaveCard.disabled = true;
                            fullScreenLoader.startLoader();
                            saveSecondCard();
                        });

                        function saveSecondCard() {
                            // Device fingerprint generated once in initObservable (ThreatMetrix session id).
                            var dfp = window.netpayDeviceFingerprint || '';
                            var cardNumber = $("#cardNumber").val();
                            var cardNumberFormat = cardNumber.replace(/-/g, "");
                            var expirationDate = $("#expirationDate").val();
                            var month = expirationDate.slice(0, 2);
                            var year = expirationDate.slice(2);
                            if (year[0] === '/') {
                                year = year.slice(1);
                            }
                            var cvv = $("#cvv").val();
                            let cardInformation = {
                                cardNumber: cardNumberFormat,
                                expMonth: month,
                                expYear: year,
                                cvv2: cvv,
                                deviceFingerPrint : dfp
                            };
                            NetPay.token.create(cardInformation, success, error);

                            function success(e) {
                                console.log("Token created successfully");
                                var chargeUrl = urlBuilder.createUrl('/payment/charges', {});
                                var response = $.parseJSON(e.message.data);
                                var customPayload = {
                                    referenceID: window.referenceID,
                                    orderId: '1',
                                    token: response.token,
                                    deviceInformation: '',
                                    paymentmethod: 'savecc',
                                    msicount: '0', 
                                    cvv: cardInformation.cvv2,
                                    saveCc: false,
                                    cardselected: false,
                                };
                                storage.post(
                                    chargeUrl, 
                                    JSON.stringify(customPayload),
                                    true
                                ).done(
                                    function (response) {
                                        fullScreenLoader.startLoader();
                                        console.log('Card saved successfully');
                                        window.location.reload();
                                    }
                                ).fail(
                                    function (response) {
                                        console.log('Error saving card');
                                        errorProcessor.process(response);
                                        btnSaveCard.disabled = false;
                                        let form = document.getElementById("creditCardForm");
                                        form.reset();
                                    }
                                ).always(fullScreenLoader.stopLoader);
                            }
                
                            function error(e) {
                                fullScreenLoader.stopLoader();
                                console.log("Something went wrong!");
                                btnSaveCard.disabled = false;
                                let form = document.getElementById("creditCardForm");
                                form.reset();
                            }
                        }

                        $("#netpay_cc_type_cvv_div").hide();
                        $("#netpay_placeorder").hide();

                        if (!additionalValidators.validate()) {
                            return;
                        }

                        NetPay.setApiKey(window.checkoutConfig.payment.netpay.public_key);
                        NetPay.setSandboxMode(self.getMode());
                        if (Object.keys(window.checkoutConfig.payment.netpay.msiValues).length === 1 || window.checkoutConfig.payment.netpay.configManager.promotionEnable == "0") {
                            var showMsiSelect = false;
                            $("#selectMSI").hide();
                        }

                        var selectElement = document.getElementById('netpay_save_card_id');
                        var saveCc = false;
                        selectElement?.addEventListener('change', function() {
                        var selectedValue = selectElement.value;
                        saveCc = selectedValue == 1 ? true : false;
                        });

                        var formError3DS = document.getElementById('ErrorConfirm3DS');
                        formError3DS.style.display = 'none';

                        NetPay.form.generate("netpay-form", success, error, { title: "", submitText: "" });
                        window.NetPay = NetPay;
                        var _this = this;

                        function success(e) {
                            console.log("Token created successfully");
                            var response = $.parseJSON(e.message.data);
                            // 3DS device data collection must finish before charging, otherwise the
                            // gateway cannot enrol the card (veresEnrolled = U). Block until ready.
                            if (!window.referenceID) {
                                fullScreenLoader.stopLoader();
                                alert('Estamos preparando el pago seguro. Espera unos segundos e intenta de nuevo.');
                                self.isPlaceOrderActionAllowed(true);
                                return;
                            }
                            self.clearThreeDsStorage();
                            self.isPlaceOrderActionAllowed(false);
                            self.getPlaceOrderDeferredObject()
                            .fail(
                                function () {
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                                function (orderId) {
                                    var chargeUrl = urlBuilder.createUrl('/payment/charges', {});
                                    var msiShowValues = window.checkoutConfig.payment.netpay.msiValues;
                                    var msiShowValues = Object.keys(msiShowValues).length;
                                    showMsiSelect = msiShowValues > 0 ? true : false;

                                    let deviceInfo = JSON.stringify(NetPay.form.deviceInformation())

                                    if (showMsiSelect) {
                                        var customPayload = {
                                            referenceID: window.referenceID,
                                            orderId: orderId,
                                            token: response.token,
                                            deviceInformation: deviceInfo,
                                            paymentmethod: 'card',
                                            deviceFingerPrint: window.netpayDeviceFingerprint,
                                            msicount: $('#netpay_payment_msi_id').val(),
                                            saveCc: saveCc,
                                            cardselected: false,
                                        };
                                    } else {
                                        var customPayload = {
                                            referenceID: window.referenceID,
                                            orderId: orderId,
                                            token: response.token,
                                            deviceInformation: deviceInfo,
                                            paymentmethod: 'card',
                                            deviceFingerPrint: window.netpayDeviceFingerprint,
                                            saveCc: saveCc,
                                            cardselected: false,
                                        };
                                    }
                                    fullScreenLoader.startLoader();
                                    storage.post(
                                        chargeUrl, 
                                        JSON.stringify(customPayload),
                                        true
                                    ).done(
                                        function (response) {
                                            var responseBody = JSON.parse(response)
                                            if (responseBody.status == 'success') {
                                                fullScreenLoader.startLoader();
                                                var url = responseBody.url;
                                                window.location.href = url;
                                            } else {
                                                var objeto = response;
                                                var objeto2 = JSON.parse(objeto);
                                                var tds = objeto2.threeDSecureResponse || {};
                                                // WooCommerce contract: a real challenge needs all three fields. Otherwise
                                                // it is frictionless and the backend reads the true state before confirming.
                                                var hasChallenge = !!(tds.acsUrl && tds.paReq && tds.authenticationTransactionID);
                                                if (hasChallenge) {
                                                    netpay3ds.proceed(_this, tds.acsUrl, tds.paReq, tds.authenticationTransactionID, callbackProceed);
                                                } else {
                                                    confirm(responseBody.transactionTokenId, null);
                                                }

                                                function callbackProceed(_this, processorTransactionId, status) {
                                                    fullScreenLoader.startLoader();
                                                    if (status == 'success') {
                                                        confirm(responseBody.transactionTokenId, processorTransactionId);
                                                    } else {
                                                        console.log('error');
                                                        errorProcessor.process(response)
                                                    }
                                                }

                                                function confirm(transactionTokenId ,processorTransactionId) {
                                                    if (processorTransactionId == null) {
                                                        processorTransactionId = 'null';
                                                    }
                                                    var customPayload = {
                                                        orderId: '1',
                                                        token: transactionTokenId,
                                                        deviceInformation: deviceInfo,
                                                        paymentmethod: '3dsConfirm',
                                                        saveCc: false,
                                                        msicount: '0',
                                                        cvv: processorTransactionId,
                                                        cardselected: false,
                                                        referenceID: window.referenceID
                                                    };
                                                    storage.post(
                                                        chargeUrl,
                                                        JSON.stringify(customPayload),
                                                        true
                                                    ).done(
                                                        function (response) {
                                                            var confirmBody = JSON.parse(response);
                                                            // Only a confirmed success redirects to the success page. A FAILED/review
                                                            // result (e.g. Decision Manager) must surface the error, not a false success.
                                                            if (confirmBody.status === 'success' && confirmBody.urlConfirmBefore3ds) {
                                                                window.location.href = confirmBody.urlConfirmBefore3ds;
                                                                return;
                                                            }
                                                            console.log('NetPay: 3DS confirm not successful', confirmBody.status, confirmBody.responseMsg || '');
                                                            fullScreenLoader.stopLoader();
                                                            window.NetPay.form.reset();
                                                            var netpayFormNotOk = document.getElementById("netpay-form");
                                                            if (netpayFormNotOk) {
                                                                netpayFormNotOk.disabled = true;
                                                                netpayFormNotOk.style.opacity = 0.4;
                                                            }
                                                            var formErrorNotOk = document.getElementById('ErrorConfirm3DS');
                                                            if (formErrorNotOk) {
                                                                if (confirmBody.responseMsg) {
                                                                    formErrorNotOk.textContent = confirmBody.responseMsg;
                                                                }
                                                                formErrorNotOk.style.display = 'block';
                                                            }
                                                        }
                                                    ).fail(
                                                        function (response) {
                                                            console.log('fail');
                                                            fullScreenLoader.stopLoader;
                                                            errorProcessor.process(response)
                                                            window.NetPay.form.reset();
                                                            const netpayFormBefore3DS = document.getElementById("netpay-form");
                                                            netpayFormBefore3DS.disabled = true;
                                                            netpayFormBefore3DS.style.opacity = 0.4;
                                                            var formError3DS2 = document.getElementById('ErrorConfirm3DS');
                                                            formError3DS2.style.display = 'block';
                                                        }
                                                    ).always(fullScreenLoader.stopLoader);
                                                }
                                            }
                                        }
                                    ).fail(
                                        function (response) {
                                            console.log('fail');
                                            errorProcessor.process(response);
                                            window.NetPay.form.reset();
                                            const netpayFormBeforeError = document.getElementById("netpay-form");
                                            netpayFormBeforeError.disabled = true; 
                                            netpayFormBeforeError.style.opacity = 0.4;
                                        }
                                    ).always(fullScreenLoader.stopLoader);
                                }
                            );
                        }

                        function error(e) {
                            fullScreenLoader.stopLoader;
                            console.log("Something went wrong!");
                            console.log(e);
                        }
                    })
                    return self;
                }
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                event.preventDefault();
                var self = this;
                var _this = this;
                var token = $('#netpay_payment_cc_id').val();
                var cvv = $('#netpay_cc_cid').val();
                var showMsiSelect;
                if (Object.keys(window.checkoutConfig.payment.netpay.msiValues).length === 1) {
                    showMsiSelect = false;
                    $("#selectMSI").hide();
                }
                if (event) {
                    event.preventDefault();
                }
                // 3DS device data collection must finish before charging (see note above).
                if (!window.referenceID) {
                    alert('Estamos preparando el pago seguro. Espera unos segundos e intenta de nuevo.');
                    return this;
                }
                self.clearThreeDsStorage();
                self.isPlaceOrderActionAllowed(false);
                self.getPlaceOrderDeferredObject()
                .fail(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function (orderId) {
                        let deviceInfo = JSON.stringify(NetPay.form.deviceInformation())
                        var chargeUrl = urlBuilder.createUrl('/payment/charges', {});
                        var msiShowValues = window.checkoutConfig.payment.netpay.msiValues;
                        var msiShowValues = Object.keys(msiShowValues).length;
                        showMsiSelect = msiShowValues > 0 ? true : false;
                        if (showMsiSelect) {
                            var customPayload = {
                                referenceID: window.referenceID,
                                orderId: orderId,
                                paymentmethod: 'card',
                                token: token,
                                deviceInformation: deviceInfo,
                                deviceFingerPrint: window.netpayDeviceFingerprint,
                                msicount: $('#netpay_payment_msi_id').val(),
                                saveCc: false,
                                cvv: cvv,
                                cardSelected: true
                            };
                        } else {
                            var customPayload = {
                                referenceID: window.referenceID,
                                orderId: orderId,
                                paymentmethod: 'card',
                                token: token,
                                deviceInformation: deviceInfo,
                                deviceFingerPrint: window.netpayDeviceFingerprint,
                                msicount: null,
                                saveCc: false,
                                cvv: cvv,
                                cardSelected: true
                            };
                        }
                        fullScreenLoader.startLoader();
                        storage.post(
                            chargeUrl, 
                            JSON.stringify(customPayload),
                            true
                        ).done(
                            function(response){
                                var responseBody = JSON.parse(response)
                                if (responseBody.status === 'success') {
                                    var url = responseBody.url;
                                    console.log('success');
                                    window.location.href = url;
                                }else{
                                    var objeto = response;
                                    var objeto2 = JSON.parse(objeto);
                                    var tds = objeto2.threeDSecureResponse || {};
                                    // WooCommerce contract: a real challenge needs all three fields. Otherwise
                                    // it is frictionless and the backend reads the true state before confirming.
                                    var hasChallenge = !!(tds.acsUrl && tds.paReq && tds.authenticationTransactionID);
                                    if (hasChallenge) {
                                        netpay3ds.proceed(_this, tds.acsUrl, tds.paReq, tds.authenticationTransactionID, callbackProceed);
                                    } else {
                                        confirm(responseBody.transactionTokenId, null);
                                    }

                                    function callbackProceed(_this, processorTransactionId, status) {
                                        fullScreenLoader.startLoader();
                                        if (status == 'success') {
                                            confirm(responseBody.transactionTokenId, processorTransactionId);                                                      
                                        } else {
                                            console.log('error');
                                            errorProcessor.process(response)
                                        }
                                    }

                                    function confirm(transactionTokenId, processorTransactionId) {
                                        if (processorTransactionId == null) {
                                            processorTransactionId = 'null';
                                        }
                                        var customPayload = {
                                            orderId: '1',
                                            token: transactionTokenId,
                                            deviceInformation: deviceInfo,
                                            paymentmethod: '3dsConfirm',
                                            saveCc: false,
                                            cvv: processorTransactionId,
                                            cardselected: false,
                                            referenceID: window.referenceID
                                        };
                                        storage.post(
                                            chargeUrl,
                                            JSON.stringify(customPayload),
                                            true
                                        ).done(
                                            function (response) {
                                                var confirmBody = JSON.parse(response);
                                                // Only a confirmed success redirects to the success page. A FAILED/review
                                                // result (e.g. Decision Manager) must surface the error, not a false success.
                                                if (confirmBody.status === 'success' && confirmBody.urlConfirmBefore3ds) {
                                                    window.location.href = confirmBody.urlConfirmBefore3ds;
                                                    return;
                                                }
                                                console.log('NetPay: 3DS confirm not successful', confirmBody.status, confirmBody.responseMsg || '');
                                                fullScreenLoader.stopLoader();
                                                var formErrorNotOk = document.getElementById('ErrorConfirm3DS');
                                                if (formErrorNotOk) {
                                                    if (confirmBody.responseMsg) {
                                                        formErrorNotOk.textContent = confirmBody.responseMsg;
                                                    }
                                                    formErrorNotOk.style.display = 'block';
                                                }
                                            }
                                        )
                                        .fail(
                                            function (response) {
                                                console.log('fail');
                                                errorProcessor.process(response);
                                                var formError3DS2 = document.getElementById('ErrorConfirm3DS');
                                                formError3DS2.style.display = 'block';
                                            }
                                        ).always(fullScreenLoader.stopLoader);
                                    }
                                }
                            }
                        ).fail(
                            function (response) {
                                console.log('fail');
                                errorProcessor.process(response);
                            }
                        ).always(fullScreenLoader.stopLoader);
                    }
                );
                return self;
            },
            onCreditCardSelect: function() {
                var self = this;
                var token = $('#netpay_payment_cc_id').val();
                if (token) {
                    $("#netpay-form").hide();
                    $("#netpay_cc_type_cvv_div").show();
                    $("#netpay_placeorder").show();
                    self.isCreditCardSelected = true;
                } else {
                    $("#netpay-form").show();
                    $("#netpay_cc_type_cvv_div").hide();
                    $("#netpay_placeorder").hide();
                    self.isCreditCardSelected = false;
                }
            }
        });
    }
);