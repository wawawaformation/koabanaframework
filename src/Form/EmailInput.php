<?php declare(strict_types=1);

namespace Koabana\Form;

class EmailInput extends Field
{
    public function render(): string
    {
        $value = htmlspecialchars((string)$this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"email\" name=\"{$this->name}\" value=\"{$value}\"{$attributes}>";
    }
}
