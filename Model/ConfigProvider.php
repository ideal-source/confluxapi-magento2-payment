<?php

declare(strict_types=1);

namespace Conflux\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    private array $methodCodes = [
        Direct::METHOD_CODE,
        Checkout::METHOD_CODE,
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    private array $methods = [];

    public function __construct(
        PaymentHelper $paymentHelper,
        private readonly UrlInterface $urlBuilder
    ) {
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    public function getConfig(): array
    {
        $config = [
            'payment' => [
                'conflux' => [
                    'redirectUrls' => [
                        Direct::METHOD_CODE => $this->urlBuilder->getUrl('conflux/direct/start', ['_secure' => true]),
                        Checkout::METHOD_CODE => $this->urlBuilder->getUrl('conflux/checkout/start', ['_secure' => true]),
                    ],
                ],
            ],
        ];

        return $config;
    }
}
