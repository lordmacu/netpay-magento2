define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'netpay',
                component: 'Netpay_Payment/js/view/payment/method-renderer/netpay'
            },
            {
                type: 'netpaycash',
                component: 'Netpay_Payment/js/view/payment/method-renderer/netpay-cash'
            },
        );
        return Component.extend({});
    }
);