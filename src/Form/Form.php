<?php declare(strict_types=1);

namespace Koabana\Form;

use Psr\Http\Message\ServerRequestInterface;

class Form
{
    private string $name;
    /** @var array<string, Field> */
    private array $fields = [];
    private ?object $entity = null;
    private Validator $validator;
    private ?string $csrfToken = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->validator = new Validator();
    }

    /**
     * Ajoute un champ au formulaire
     */
    public function add(Field $field): void
    {
        $this->fields[$field->getName()] = $field;
    }

    /**
     * Récupère un champ par son nom
     */
    public function field(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Retourne tous les champs
     * @return array<string, Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Hydrate les champs avec des données
     * @param array<string, mixed> $data
     */
    public function fill(array $data): void
    {
        foreach ($this->fields as $name => $field) {
            $field->setValue($data[$name] ?? null);
        }
    }

    /**
     * Bind le formulaire à une Entity
     * Hydrate les champs depuis les propriétés de l'entity
     */
    public function bind(object $entity): void
    {
        $this->entity = $entity;

        // Récupère les propriétés publiques/getter de l'entity
        foreach ($this->fields as $name => $field) {
            $methodName = 'get' . ucfirst($name);
            if (method_exists($entity, $methodName)) {
                $field->setValue($entity->$methodName());
            }
        }
    }

    /**
     * Valide le formulaire
     */
    public function validate(): bool
    {
        return $this->validator->validate($this->fields);
    }

    /**
     * Retourne les erreurs pour un champ ou tous les champs
     * @return array<string, array<string>>|array<string>
     */
    public function errors(?string $fieldName = null): array
    {
        if ($fieldName === null) {
            $allErrors = [];
            foreach ($this->fields as $name => $field) {
                if ($field->hasErrors()) {
                    $allErrors[$name] = $field->getErrors();
                }
            }
            return $allErrors;
        }

        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName]->getErrors();
        }

        return [];
    }

    /**
     * Récupère les données du formulaire
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = [];

        foreach ($this->fields as $name => $field) {
            $data[$name] = $field->getValue();
        }

        return $data;
    }

    /**
     * Récupère ou crée l'Entity avec les données validées
     */
    public function getEntity(): ?object
    {
        if ($this->entity === null) {
            return null;
        }

        // Hydrate l'entity avec les données du formulaire
        foreach ($this->fields as $name => $field) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($this->entity, $methodName)) {
                $this->entity->$methodName($field->getValue());
            }
        }

        return $this->entity;
    }

    /**
     * Définit le token CSRF
     */
    public function setCsrfToken(string $token): void
    {
        $this->csrfToken = $token;
    }

    /**
     * Retourne le champ caché CSRF
     */
    public function csrf(): string
    {
        if ($this->csrfToken === null) {
            return '';
        }

        $token = htmlspecialchars($this->csrfToken, ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\">";
    }

    /**
     * Retourne la balise d'ouverture du formulaire
     * @param array<string, mixed> $attributes
     */
    public function open(string $action = '', string $method = 'POST', array $attributes = []): string
    {
        $methodUpper = strtoupper($method);
        $attrs = '';

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $attrs .= " {$key}";
            } elseif ($value !== false) {
                $attrs .= " {$key}=\"" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        $actionAttr = $action ? " action=\"{$action}\"" : '';
        return "<form name=\"{$this->name}\" method=\"{$methodUpper}\"{$actionAttr}{$attrs}>";
    }

    /**
     * Retourne la balise de fermeture du formulaire
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * Helper pour créer rapidement un formulaire depuis une requête
     */
    public static function createFromRequest(string $name, ServerRequestInterface $request): self
    {
        $form = new self($name);
        $form->fill((array)$request->getParsedBody());
        return $form;
    }

    /**
     * Crée un formulaire bindé à une Entity
     * Le callback doit ajouter les champs au formulaire
     *
     * @param string $name Nom du formulaire
     * @param object $entity Entité à binder
     * @param callable(self): void $fieldBuilder Callback pour ajouter les champs
     * @return self Formulaire configuré et hydraté depuis l'entity
     *
     * @example
     *   $form = Form::fromEntity('contact', $contact, function(Form $form) {
     *       $form->add(new TextInput('name', ['required' => true]));
     *       $form->add(new EmailInput('email', ['required' => true]));
     *   });
     */
    public static function fromEntity(
        string $name,
        object $entity,
        callable $fieldBuilder,
    ): self {
        $form = new self($name);
        $form->bind($entity);
        
        // Appel le callback pour construire les champs
        $fieldBuilder($form);
        
        // Hydrate depuis l'entity
        foreach ($form->getFields() as $field) {
            $methodName = 'get' . ucfirst($field->getName());
            if (method_exists($entity, $methodName)) {
                $field->setValue($entity->$methodName());
            }
        }
        
        return $form;
    }
}