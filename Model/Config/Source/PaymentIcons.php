<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentIcons implements OptionSourceInterface
{
    /**
     * @var array<string, string>
     */
    public const OPTIONS = [
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'amex' => 'American Express',
        'discover' => 'Discover',
        'jcb' => 'JCB',
        'diners' => 'Diners Club',
        'cash_app' => 'Cash App',
        'klarna' => 'Klarna',
        'ideal' => 'iDEAL',
        'bancontact' => 'Bancontact',
        'blik' => 'BLIK',
        'swish' => 'Swish',
        'twint' => 'TWINT',
        'wero' => 'Wero',
        'apple_pay' => 'Apple Pay',
        'google_pay' => 'Google Pay',
        'pse' => 'PSE',
        'nequi' => 'Nequi',
    ];

    public function toOptionArray(): array
    {
        $options = [];

        foreach (self::OPTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        return $options;
    }
}
