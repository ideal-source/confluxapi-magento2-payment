<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class PaymentIcons extends Value
{
    public function beforeSave(): self
    {
        $value = $this->getValue();

        if (is_array($value)) {
            $this->setValue(implode(',', $this->normalizeValues($value)));
        }

        return parent::beforeSave();
    }

    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                $normalized = array_merge($normalized, $this->normalizeValues($value));
                continue;
            }

            $value = trim((string)$value);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
