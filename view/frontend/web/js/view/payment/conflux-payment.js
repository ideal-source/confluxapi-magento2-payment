define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'conflux_direct',
            component: 'Conflux_Payment/js/view/payment/method-renderer/direct-method'
        },
        {
            type: 'conflux_checkout',
            component: 'Conflux_Payment/js/view/payment/method-renderer/checkout-method'
        }
    );

    return Component.extend({});
});
