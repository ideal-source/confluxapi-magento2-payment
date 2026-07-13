<?php

declare(strict_types=1);

namespace Conflux\Payment\Model;

use Conflux\Payment\Model\Config\Source\PaymentIcons;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    private const ICON_ASSET_PATH = 'Conflux_Payment::images/payment-icons/%s.svg';

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
        private readonly UrlInterface $urlBuilder,
        private readonly AssetRepository $assetRepository
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
                    'icons' => $this->getIcons(),
                ],
            ],
        ];

        return $config;
    }

    private function getIcons(): array
    {
        $icons = [];

        foreach ($this->methods as $code => $method) {
            $icons[$code] = $this->buildIcons((string)$method->getConfigData('icons'));
        }

        return $icons;
    }

    private function buildIcons(string $configuredIcons): array
    {
        $icons = [];
        $selectedIcons = array_filter(array_map('trim', explode(',', $configuredIcons)));

        foreach ($selectedIcons as $icon) {
            if (!isset(PaymentIcons::OPTIONS[$icon])) {
                continue;
            }

            $icons[] = [
                'code' => $icon,
                'label' => PaymentIcons::OPTIONS[$icon],
                'url' => $this->assetRepository->createAsset(sprintf(self::ICON_ASSET_PATH, $icon))->getUrl(),
            ];
        }

        return $icons;
    }
}
