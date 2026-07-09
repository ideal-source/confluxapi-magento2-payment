<?php

declare(strict_types=1);

namespace Conflux\Payment\Model;

use Conflux\Payment\Block\Info\Direct as DirectInfoBlock;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\AbstractMethod;

class Direct extends AbstractMethod
{
    public const METHOD_CODE = 'conflux_direct';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = DirectInfoBlock::class;

    /**
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var string[]
     */
    private array $cardAdditionalFields = [
        'card_number',
        'expiry_month',
        'expiry_year',
        'cvv',
        'card_type',
        'browser_screen_height',
        'browser_screen_width',
        'browser_user_agent',
        'browser_time_zone',
        'browser_language',
    ];

    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData('additional_data');
        if ($additionalData instanceof DataObject) {
            $additionalData = $additionalData->getData();
        }

        if (!is_array($additionalData)) {
            return $this;
        }

        $info = $this->getInfoInstance();
        foreach ($this->cardAdditionalFields as $field) {
            if (array_key_exists($field, $additionalData)) {
                $info->setAdditionalInformation('conflux_' . $field, $additionalData[$field]);
            }
        }

        if (!empty($additionalData['card_number'])) {
            $info->setAdditionalInformation(
                'conflux_card_number_masked',
                $this->maskCardNumber((string)$additionalData['card_number'])
            );
        }

        return $this;
    }

    private function maskCardNumber(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D+/', '', $cardNumber) ?: '';

        if ($cardNumber === '') {
            return '';
        }

        return str_repeat('*', max(strlen($cardNumber) - 4, 0)) . substr($cardNumber, -4);
    }
}
