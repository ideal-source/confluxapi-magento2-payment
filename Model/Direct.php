<?php

declare(strict_types=1);

namespace Conflux\Payment\Model;

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

        return $this;
    }

}
