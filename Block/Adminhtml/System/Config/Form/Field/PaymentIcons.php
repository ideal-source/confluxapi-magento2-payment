<?php

declare(strict_types=1);

namespace Conflux\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class PaymentIcons extends Field
{
    protected function _getElementHtml(AbstractElement $element): string
    {
        $selectedValues = $this->getSelectedValues((string)$element->getValue());
        $html = '<style>'
            . '.conflux-payment-icons-config{display:grid;gap:8px 12px;grid-template-columns:repeat(2, 145px);max-width:302px;}'
            . '.conflux-payment-icons-config__option{align-items:center;box-sizing:border-box;cursor:pointer;display:flex;gap:6px;margin:0;min-height:24px;min-width:0;white-space:nowrap;}'
            . '.conflux-payment-icons-config__option input{flex:0 0 auto;margin:0;}'
            . '.conflux-payment-icons-config__option span{display:block;overflow:hidden;text-overflow:ellipsis;}'
            . '</style>';
        $html .= '<input type="hidden" name="' . $this->escapeHtmlAttr($element->getName()) . '[]" value="" />';
        $html .= '<div class="conflux-payment-icons-config">';

        foreach ($element->getValues() as $option) {
            $value = (string)($option['value'] ?? '');
            $label = (string)($option['label'] ?? $value);
            $id = $element->getHtmlId() . '_' . $value;
            $checked = in_array($value, $selectedValues, true) ? ' checked="checked"' : '';

            $html .= '<label class="conflux-payment-icons-config__option" for="' . $this->escapeHtmlAttr($id) . '">';
            $html .= '<input type="checkbox" id="' . $this->escapeHtmlAttr($id) . '"'
                . ' name="' . $this->escapeHtmlAttr($element->getName()) . '[]"'
                . ' value="' . $this->escapeHtmlAttr($value) . '"' . $checked . ' />';
            $html .= '<span>' . $this->escapeHtml($label) . '</span>';
            $html .= '</label>';
        }

        $html .= '</div>';

        return $html;
    }

    private function getSelectedValues(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }
}
