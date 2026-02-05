<?php declare(strict_types=1);

namespace Koabana\Form;

class Textarea extends Field
{
    public function render(): string
    {
        $value = htmlspecialchars((string)$this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();

        return "<textarea name=\"{$this->name}\"{$attributes}>{$value}</textarea>";
    }
}
