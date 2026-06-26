<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Checkout;

use Conflux\Payment\Model\Api\Client;
use Magento\Sales\Api\Data\OrderInterface;

class PaymentStarter
{
    public function __construct(
        private readonly Client $client,
        private readonly PayloadBuilder $payloadBuilder
    ) {
    }

    public function start(OrderInterface $order): array
    {
        return $this->client->createCheckout($this->payloadBuilder->build($order), (int)$order->getStoreId());
    }
}
