<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;

class OrderDataBuilder
{
    /**
     * @var string[]
     */
    private array $zeroDecimalCurrencies = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function buildBasePayload(OrderInterface $order, string $methodCode): array
    {
        $storeId = (int)$order->getStoreId();

        return [
            'mch_no' => $this->getConfig($methodCode, 'mch_no', $storeId),
            'gateway_no' => $this->getConfig($methodCode, 'gateway_no', $storeId),
            'mch_order_no' => (string)$order->getIncrementId(),
            'amount' => $this->toMinorUnits((float)$order->getGrandTotal(), (string)$order->getOrderCurrencyCode()),
            'currency' => (string)$order->getOrderCurrencyCode(),
        ];
    }

    public function buildCustomer(?OrderAddressInterface $address): array
    {
        if (!$address) {
            return [];
        }

        return [
            'email' => (string)$address->getEmail(),
            'country' => (string)$address->getCountryId(),
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'phone' => (string)$address->getTelephone(),
        ];
    }

    public function buildAddress(?OrderAddressInterface $address): array
    {
        if (!$address) {
            return [];
        }

        $street = $address->getStreet();

        return [
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'email' => (string)$address->getEmail(),
            'phone' => (string)$address->getTelephone(),
            'country' => (string)$address->getCountryId(),
            'state' => (string)($address->getRegionCode() ?: $address->getRegion()),
            'city' => (string)$address->getCity(),
            'address_line1' => (string)($street[0] ?? ''),
            'address_line2' => (string)($street[1] ?? ''),
            'postal_code' => (string)$address->getPostcode(),
        ];
    }

    public function buildLineItems(OrderInterface $order): array
    {
        $items = [];
        $currency = (string)$order->getOrderCurrencyCode();

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $items[] = [
                'name' => (string)$item->getName(),
                'quantity' => (int)$item->getQtyOrdered(),
                'sku' => (string)$item->getSku(),
                'unit_amount' => $this->toMinorUnits((float)$item->getPriceInclTax(), $currency),
                'total_amount' => $this->toMinorUnits((float)$item->getRowTotalInclTax(), $currency),
            ];
        }

        return $items;
    }

    public function buildCustomParams(OrderInterface $order, string $source): array
    {
        return [
            'source' => $source,
            'magento_order_id' => (string)$order->getEntityId(),
        ];
    }

    public function getConfig(string $methodCode, string $field, int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(
            'payment/' . $methodCode . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function toMinorUnits(float $amount, string $currency): int
    {
        $multiplier = in_array(strtoupper($currency), $this->zeroDecimalCurrencies, true) ? 1 : 100;

        return (int)round($amount * $multiplier);
    }
}
