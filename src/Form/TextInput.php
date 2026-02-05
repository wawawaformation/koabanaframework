<?php declare(strict_types=1);

namespace Koabana\Form;

class TextInput extends Field
{
    public function render(): string
    {
        $value = htmlspecialchars((string)$this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"text\" name=\"{$this->name}\" value=\"{$value}\"{$attributes}>";
    }
}
