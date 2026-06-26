<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Checkout;

use Conflux\Payment\Model\Api\OrderDataBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;

class PayloadBuilder
{
    public function __construct(
        private readonly OrderDataBuilder $orderDataBuilder,
        private readonly UrlInterface $urlBuilder,
        private readonly ResolverInterface $localeResolver
    ) {
    }

    public function build(OrderInterface $order): array
    {
        $methodCode = (string)$order->getPayment()->getMethod();
        $storeId = (int)$order->getStoreId();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress() ?: $billingAddress;

        $payload = $this->orderDataBuilder->buildBasePayload($order, $methodCode);
        $payload['return_url'] = $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]);
        $payload['cancel_url'] = $this->urlBuilder->getUrl('conflux/checkout/cancel', ['_secure' => true]);
        $payload['notify_url'] = $this->urlBuilder->getUrl('conflux/notify/index', ['_secure' => true]);
        $payload['description'] = 'Magento order ' . $order->getIncrementId();
        $payload['expires_in'] = $this->getExpiresIn($methodCode, $storeId);
        $payload['locale'] = $this->getLocale($methodCode, $storeId);
        $payload['customer'] = $this->orderDataBuilder->buildCustomer($billingAddress);
        $payload['billing_info'] = $this->orderDataBuilder->buildAddress($billingAddress);
        $payload['shipping_info'] = $this->orderDataBuilder->buildAddress($shippingAddress);
        $payload['line_items'] = $this->orderDataBuilder->buildLineItems($order);
        $payload['device_info'] = [
            'client_ip' => (string)$order->getRemoteIp(),
            'language' => $payload['locale'],
        ];
        $payload['custom_params'] = $this->orderDataBuilder->buildCustomParams($order, 'hosted_checkout');

        $discountAmount = $this->getDiscountAmount($order);
        if ($discountAmount > 0) {
            $payload['discount_amount'] = $discountAmount;
        }

        return $payload;
    }

    private function getExpiresIn(string $methodCode, int $storeId): int
    {
        $expiresIn = (int)$this->orderDataBuilder->getConfig($methodCode, 'expires_in', $storeId);
        if ($expiresIn <= 0) {
            $expiresIn = 7200;
        }

        return max(7200, min(86400, $expiresIn));
    }

    private function getLocale(string $methodCode, int $storeId): string
    {
        $locale = $this->localeResolver->getLocale();
        $locale = str_replace('_', '-', trim((string)$locale));

        return $locale !== '' ? $locale : 'en-US';
    }

    private function getDiscountAmount(OrderInterface $order): int
    {
        $discountAmount = abs((float)$order->getDiscountAmount());
        if ($discountAmount <= 0.0) {
            return 0;
        }

        return $this->orderDataBuilder->toMinorUnits($discountAmount, (string)$order->getOrderCurrencyCode());
    }
}
