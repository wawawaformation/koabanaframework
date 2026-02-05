<?php declare(strict_types=1);

namespace Koabana\Form;

class Validator
{
    /** @var array<string, array<string>> */
    private array $errors = [];

    /**
     * Valide un ensemble de champs selon leurs règles
     * @param array<Field> $fields
     */
    public function validate(array $fields): bool
    {
        $this->errors = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            $this->validateField($field);
        }

        return empty($this->errors);
    }

    /**
     * Valide un champ individuel
     */
    private function validateField(Field $field): void
    {
        $rules = $field->getRules();
        $value = $field->getValue();

        // Vérification required
        if (isset($rules['required'])) {
            if ($rules['required'] && (empty($value) && $value !== '0' && $value !== 0)) {
                $field->addError("Ce champ est requis.");
                return;
            }
        }

        // Si vide et pas required, on skip les autres validations
        if (empty($value) && $value !== '0' && $value !== 0) {
            return;
        }

        // Validations supplémentaires
        if (isset($rules['email'])) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $field->addError("Format d'email invalide.");
            }
        }

        if (isset($rules['minLength'])) {
            $minLength = (int)$rules['minLength'];
            if (strlen((string)$value) < $minLength) {
                $field->addError("Minimum {$minLength} caractères requis.");
            }
        }

        if (isset($rules['maxLength'])) {
            $maxLength = (int)$rules['maxLength'];
            if (strlen((string)$value) > $maxLength) {
                $field->addError("Maximum {$maxLength} caractères.");
            }
        }

        if (isset($rules['min'])) {
            $min = (int)$rules['min'];
            if ((int)$value < $min) {
                $field->addError("La valeur doit être au minimum {$min}.");
            }
        }

        if (isset($rules['max'])) {
            $max = (int)$rules['max'];
            if ((int)$value > $max) {
                $field->addError("La valeur ne doit pas dépasser {$max}.");
            }
        }

        if (isset($rules['regex'])) {
            if (!preg_match($rules['regex'], (string)$value)) {
                $field->addError("Le format n'est pas valide.");
            }
        }
    }

    /** @return array<string, array<string>> */
    public function getErrors(): array
    {
        /** @var array<string, array<string>> $errors */
        $errors = [];
        // Les erreurs sont directement stockées dans les Field
        return $errors;
    }
}
