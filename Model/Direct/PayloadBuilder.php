<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Direct;

use Conflux\Payment\Model\Api\OrderDataBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;

class PayloadBuilder
{
    private const SUPPORTED_CARD_TYPES = ['VISA', 'MASTER', 'JCB', 'DISCOVER', 'AMEX', 'DINERS'];

    public function __construct(
        private readonly OrderDataBuilder $orderDataBuilder,
        private readonly UrlInterface $urlBuilder,
        private readonly ResolverInterface $localeResolver
    ) {
    }

    public function build(OrderInterface $order): array
    {
        $methodCode = (string)$order->getPayment()->getMethod();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress() ?: $billingAddress;
        $cardDetails = $this->buildCardDetails($order);
        $cardType = $this->detectCardType($cardDetails['card_number']);

        $payload = $this->orderDataBuilder->buildBasePayload($order, $methodCode);
        $payload['way_code'] = $cardType;
        $payload['card_details'] = $cardDetails;
        $payload['return_url'] = $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]);
        $payload['notify_url'] = $this->urlBuilder->getUrl('conflux/notify/index', ['_secure' => true]);
        $payload['billing_info'] = $this->orderDataBuilder->buildAddress($billingAddress);
        $payload['shipping_info'] = $this->orderDataBuilder->buildAddress($shippingAddress);
        $payload['items'] = $this->orderDataBuilder->buildLineItems($order);
        $payload['device_info'] = [
            'client_ip' => (string)$order->getRemoteIp(),
            'language' => $this->getLanguage($order),
            'browser_info' => $this->getBrowserInfo($order),
        ];
        $payload['custom_params'] = $this->orderDataBuilder->buildCustomParams($order, 'direct');

        return $payload;
    }

    private function buildCardDetails(OrderInterface $order): array
    {
        $cardNumber = preg_replace('/\D+/', '', (string)$this->getAdditionalInformation($order, 'card_number'));
        $expiryMonth = str_pad((string)$this->getAdditionalInformation($order, 'expiry_month'), 2, '0', STR_PAD_LEFT);
        $expiryYear = substr((string)$this->getAdditionalInformation($order, 'expiry_year'), -2);
        $cvv = preg_replace('/\D+/', '', (string)$this->getAdditionalInformation($order, 'cvv'));

        if ($cardNumber === '' || $expiryMonth === '' || $expiryYear === '' || $cvv === '') {
            throw new LocalizedException(__('Please enter complete credit card information.'));
        }

        return [
            'card_number' => $cardNumber,
            'expiry_year' => $expiryYear,
            'expiry_month' => $expiryMonth,
            'cvv' => $cvv,
        ];
    }

    private function detectCardType(string $cardNumber): string
    {
        if (!$this->isValidCardNumber($cardNumber)) {
            throw new LocalizedException(__('Please enter a supported credit card number.'));
        }

        $patterns = [
            'VISA' => '/^4\d{12}(\d{3})?(\d{3})?$/',
            'MASTER' => '/^(5[1-5]\d{14}|2(2[2-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12})$/',
            'AMEX' => '/^3[47]\d{13}$/',
            'DISCOVER' => '/^(6011\d{12}|65\d{14}|64[4-9]\d{13}|622(12[6-9]|1[3-9]\d|[2-8]\d{2}|9[01]\d|92[0-5])\d{10})$/',
            'JCB' => '/^(35(2[89]|[3-8]\d)\d{12,15})$/',
            'DINERS' => '/^3(0[0-5]\d{11}|[68]\d{12})$/',
        ];

        foreach ($patterns as $cardType => $pattern) {
            if (preg_match($pattern, $cardNumber) && in_array($cardType, self::SUPPORTED_CARD_TYPES, true)) {
                return $cardType;
            }
        }

        throw new LocalizedException(__('The credit card type is not supported by Conflux.'));
    }

    private function isValidCardNumber(string $cardNumber): bool
    {
        $sum = 0;
        $alternate = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int)$cardNumber[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return $cardNumber !== '' && $sum % 10 === 0;
    }

    private function getBrowserInfo(OrderInterface $order): array
    {
        return [
            'screen_height' => (int)$this->getAdditionalInformation($order, 'browser_screen_height'),
            'screen_width' => (int)$this->getAdditionalInformation($order, 'browser_screen_width'),
            'user_agent' => (string)$this->getAdditionalInformation($order, 'browser_user_agent'),
            'time_zone' => (string)($this->getAdditionalInformation($order, 'browser_time_zone') ?: 'UTC'),
        ];
    }

    private function getLanguage(OrderInterface $order): string
    {
        $language = (string)($this->getAdditionalInformation($order, 'browser_language') ?: $this->localeResolver->getLocale());
        $language = str_replace('_', '-', trim($language));

        return $language !== '' ? $language : 'en-US';
    }

    private function getAdditionalInformation(OrderInterface $order, string $field): mixed
    {
        return $order->getPayment()->getAdditionalInformation('conflux_' . $field);
    }
}
