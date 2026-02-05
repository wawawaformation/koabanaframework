<?php declare(strict_types=1);

namespace Koabana\Form;

class Select extends Field
{
    /** @var array<string|int, string> */
    private array $options = [];

    /**
     * @param string $name
     * @param array<string|int, string> $options
     * @param array<string, mixed> $attributes
     */
    public function __construct(string $name, array $options = [], array $attributes = [])
    {
        parent::__construct($name, $attributes);
        $this->options = $options;
    }

    /** @return array<string|int, string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function render(): string
    {
        $attributes = $this->getHtmlAttributes();
        $html = "<select name=\"{$this->name}\"{$attributes}>";

        foreach ($this->options as $value => $label) {
            $selected = $this->value === $value ? ' selected' : '';
            $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
            $html .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
        }

        $html .= '</select>';

        return $html;
    }
}
