<?php declare(strict_types=1);

namespace Koabana\Form;

abstract class Field
{
    protected string $name;
    protected mixed $value = null;
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var array<string, mixed> */
    protected array $rules = [];
    /** @var array<string> */
    protected array $errors = [];

    /**
     * @param string $name
     * @param array<string, mixed> $attributes
     */
    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->extractRules();
    }

    /**
     * Extrait les règles de validation des attributs
     */
    private function extractRules(): void
    {
        /** @var array<string> $validationKeys */
        $validationKeys = ['required', 'email', 'minLength', 'maxLength', 'regex', 'min', 'max', 'match'];
        
        foreach ($validationKeys as $key) {
            if (isset($this->attributes[$key])) {
                $this->rules[$key] = $this->attributes[$key];
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /** @return array<string, mixed> */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function hasRule(string $rule): bool
    {
        return isset($this->rules[$rule]);
    }

    public function getRule(string $rule): mixed
    {
        return $this->rules[$rule] ?? null;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /** @return array<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retourne les attributs HTML (exclut les règles de validation)
     */
    protected function getHtmlAttributes(): string
    {
        /** @var array<string> $validationKeys */
        $validationKeys = ['required', 'email', 'minLength', 'maxLength', 'regex', 'min', 'max', 'match'];
        $html = '';

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $validationKeys, true)) {
                if ($value === true) {
                    $html .= " {$key}";
                } elseif ($value !== false) {
                    $html .= " {$key}=\"" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }

        return $html;
    }

    /**
     * Méthode abstraite pour le rendu HTML
     */
    abstract public function render(): string;
}
