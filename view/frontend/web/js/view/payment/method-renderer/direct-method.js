define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'ko',
    'mage/translate'
], function (Component, redirectOnSuccessAction, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Conflux_Payment/payment/direct',
            redirectAfterPlaceOrder: false
        },

        cardNumber: ko.observable(''),
        expiryMonth: ko.observable(''),
        expiryYear: ko.observable(''),
        cvv: ko.observable(''),

        initialize: function () {
            this._super();

            this.cardNumber.subscribe(function (value) {
                this.limitNumericObservable(this.cardNumber, value, 19);
            }, this);
            this.expiryMonth.subscribe(function (value) {
                this.limitNumericObservable(this.expiryMonth, value, 2);
            }, this);
            this.expiryYear.subscribe(function (value) {
                this.limitNumericObservable(this.expiryYear, value, 2);
            }, this);
            this.cvv.subscribe(function (value) {
                this.limitNumericObservable(this.cvv, value, 4);
            }, this);

            return this;
        },

        getData: function () {
            var browserInfo = this.getBrowserInfo();

            return {
                method: this.item.method,
                additional_data: {
                    card_number: this.normalizeCardNumber(this.cardNumber()),
                    expiry_month: this.expiryMonth(),
                    expiry_year: this.expiryYear(),
                    cvv: this.cvv(),
                    card_type: this.getCardType(),
                    browser_screen_height: browserInfo.screen_height,
                    browser_screen_width: browserInfo.screen_width,
                    browser_user_agent: browserInfo.user_agent,
                    browser_time_zone: browserInfo.time_zone,
                    browser_language: browserInfo.language
                }
            };
        },

        getCardType: function () {
            var number = this.normalizeCardNumber(this.cardNumber());

            if (!this.isValidCardNumber(number)) {
                return '';
            }

            if (/^4\d{12}(\d{3})?(\d{3})?$/.test(number)) {
                return 'VISA';
            }

            if (/^(5[1-5]\d{14}|2(2[2-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12})$/.test(number)) {
                return 'MASTER';
            }

            if (/^3[47]\d{13}$/.test(number)) {
                return 'AMEX';
            }

            if (/^(6011\d{12}|65\d{14}|64[4-9]\d{13}|622(12[6-9]|1[3-9]\d|[2-8]\d{2}|9[01]\d|92[0-5])\d{10})$/.test(number)) {
                return 'DISCOVER';
            }

            if (/^(35(2[89]|[3-8]\d)\d{12,15})$/.test(number)) {
                return 'JCB';
            }

            if (/^3(0[0-5]\d{11}|[68]\d{12})$/.test(number)) {
                return 'DINERS';
            }

            return '';
        },

        getCardTypeLabel: function () {
            return this.getCardType() || $t('Card');
        },

        validate: function () {
            if (!this.isValidCardNumber(this.normalizeCardNumber(this.cardNumber())) || !this.getCardType()) {
                this.messageContainer.addErrorMessage({
                    message: $t('Please enter a supported credit card number.')
                });
                return false;
            }

            if (!this.isValidExpiry()) {
                this.messageContainer.addErrorMessage({
                    message: $t('Please enter a valid credit card expiry date.')
                });
                return false;
            }

            if (!/^\d{3,4}$/.test(this.cvv())) {
                this.messageContainer.addErrorMessage({
                    message: $t('Please enter a valid credit card security code.')
                });
                return false;
            }

            return true;
        },

        afterPlaceOrder: function () {
            redirectOnSuccessAction.redirectUrl = window.checkoutConfig.payment.conflux.redirectUrls[this.item.method];
            redirectOnSuccessAction.execute();
        },

        normalizeCardNumber: function (number) {
            return String(number || '').replace(/\D+/g, '');
        },

        limitNumericObservable: function (observable, value, maxLength) {
            var normalized = String(value || '').replace(/\D+/g, '').slice(0, maxLength);

            if (value !== normalized) {
                observable(normalized);
            }
        },

        isValidCardNumber: function (number) {
            var sum = 0,
                alternate = false,
                i,
                digit;

            if (!number) {
                return false;
            }

            for (i = number.length - 1; i >= 0; i--) {
                digit = parseInt(number.charAt(i), 10);

                if (alternate) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }

                sum += digit;
                alternate = !alternate;
            }

            return sum % 10 === 0;
        },

        isValidExpiry: function () {
            var month = parseInt(this.expiryMonth(), 10),
                year = parseInt(this.expiryYear(), 10),
                now = new Date(),
                currentYear = now.getFullYear() % 100,
                currentMonth = now.getMonth() + 1;

            if (isNaN(month) || month < 1 || month > 12 || isNaN(year)) {
                return false;
            }

            return year > currentYear || (year === currentYear && month >= currentMonth);
        },

        getBrowserInfo: function () {
            return {
                screen_height: window.screen ? window.screen.height : 0,
                screen_width: window.screen ? window.screen.width : 0,
                user_agent: window.navigator ? window.navigator.userAgent : '',
                time_zone: this.getTimeZone(),
                language: window.navigator ? (window.navigator.language || 'en-US') : 'en-US'
            };
        },

        getTimeZone: function () {
            var offset = new Date().getTimezoneOffset(),
                sign = offset <= 0 ? '+' : '-',
                absoluteOffset = Math.abs(offset),
                hours = String(Math.floor(absoluteOffset / 60)).padStart(2, '0');

            return 'UTC' + sign + hours;
        }
    });
});
