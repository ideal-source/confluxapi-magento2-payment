<?php

declare(strict_types=1);

namespace Conflux\Payment\Block\Info;

use Magento\Payment\Block\Info;

class Direct extends Info
{
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $response = (array)$payment->getAdditionalInformation('conflux_response');
        $notification = (array)$payment->getAdditionalInformation('conflux_last_notification');
        $notificationData = is_array($notification['data'] ?? null) ? $notification['data'] : [];
        $cardNumber = (string)$payment->getAdditionalInformation('conflux_card_number_masked');

        if ($cardNumber === '') {
            $cardNumber = $this->maskCardNumber((string)$payment->getAdditionalInformation('conflux_card_number'));
        }

        $details = [];
        $this->addIfNotEmpty($details, (string)__('Card Number'), $cardNumber);
        $this->addIfNotEmpty($details, (string)__('Card Type'), (string)$payment->getAdditionalInformation('conflux_card_type'));
        $this->addIfNotEmpty($details, (string)__('Expiration Date'), $this->formatExpiry(
            (string)$payment->getAdditionalInformation('conflux_expiry_month'),
            (string)$payment->getAdditionalInformation('conflux_expiry_year')
        ));
        $this->addIfNotEmpty($details, (string)__('Conflux Flow Order No'), $this->firstScalar([
            $payment->getLastTransId(),
            $response['flow_order_no'] ?? null,
            $notificationData['flow_order_no'] ?? null,
        ]));
        $this->addIfNotEmpty($details, (string)__('Trade Status'), $this->firstScalar([
            $notificationData['trade_status'] ?? null,
            $response['trade_status'] ?? null,
        ]));

        return $transport->addData($details);
    }

    private function addIfNotEmpty(array &$details, string $label, string $value): void
    {
        $value = trim($value);

        if ($value !== '') {
            $details[$label] = $value;
        }
    }

    private function firstScalar(array $values): string
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string)$value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function formatExpiry(string $month, string $year): string
    {
        if ($month === '' || $year === '') {
            return '';
        }

        return sprintf('%02d/%02d', (int)$month, (int)substr($year, -2));
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
