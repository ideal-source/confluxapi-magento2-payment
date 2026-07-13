define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success'
], function (Component, redirectOnSuccessAction) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Conflux_Payment/payment/checkout',
            redirectAfterPlaceOrder: false
        },

        getIconItems: function () {
            return window.checkoutConfig.payment.conflux.icons[this.item.method] || [];
        },

        afterPlaceOrder: function () {
            redirectOnSuccessAction.redirectUrl = window.checkoutConfig.payment.conflux.redirectUrls[this.item.method];
            redirectOnSuccessAction.execute();
        }
    });
});
