<?php declare(strict_types=1);

namespace Koabana\Form;

class PasswordInput extends Field
{
    public function render(): string
    {
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"password\" name=\"{$this->name}\"{$attributes}>";
    }
}
