<?php declare(strict_types=1);

namespace Koabana\Form;

class Checkbox extends Field
{
    public function render(): string
    {
        $checked = (bool)$this->value ? ' checked' : '';
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"checkbox\" name=\"{$this->name}\" value=\"1\"{$checked}{$attributes}>";
    }
}
