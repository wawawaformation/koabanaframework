<?php

declare(strict_types=1);

namespace Koabana\Model\Repository;

use Koabana\Database\BDDFactory;
use Koabana\Model\Entity\AbstractEntity;

/**
 * Repository de base : accès BDD et hydratation d'entités.
 */
abstract class AbstractRepository
{
    protected string $table;
    protected string $entityClass;

    public function __construct(protected BDDFactory $bddFactory) {}

    /**
     * Retourne toutes les entités de la table
     *
     * @return array<int, AbstractEntity>
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM '.$this->table.' ORDER BY id ASC';
        $stmt = $this->statement($sql);
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateAll($row);
    }

    /**
     * Exécute une requête préparée (ou non), avec paramètres optionnels.
     *
     * @param null|array<string, mixed> $params
     */
    public function statement(string $sql, ?array $params = null): \PDOStatement
    {
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->prepare($sql);

        if (null !== $params) {
            foreach ($params as $key => $value) {
                // accepte :id ou id, on normalise
                $paramKey = \str_starts_with($key, ':') ? $key : ':'.$key;
                $stmt->bindValue($paramKey, $value);
            }
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Hydrate une seule ligne de résultat en entité.
     *
     * @param array<string, mixed> $row
     *
     * @return object
     */
    protected function hydrate(array $row): object
    {
        $class = $this->entityClass;

        if (!\class_exists($class)) {
            throw new \RuntimeException('Entity class not found: '.$class);
        }

        $entity = new $class();

        foreach ($row as $column => $value) {
            $property = $this->snakeToCamel((string) $column);
            $setter = 'set'.\ucfirst($property);

            if (!\method_exists($entity, $setter)) {
                continue;
            }

            $entity->{$setter}($this->castValueForProperty($property, $value));
        }

        return $entity;
    }

    /**
     * Hydrate plusieurs lignes de résultat en entités.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, object>
     */
    protected function hydrateAll(array $rows): array
    {
        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->hydrate($row);
        }

        return $entities;
    }

    /**
     * Convertit une chaîne snake_case en camelCase.
     *
     * @param string $string
     *
     * @return string
     */
    protected function snakeToCamel(string $string): string
    {
        return \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $string))));
    }

    /**
     * Convertit une chaîne camelCase en snake_case.
     *
     * @param string $string
     *
     * @return string
     */
    protected function camelToSnake(string $string): string
    {
        $snake = \preg_replace('/[A-Z]/', '_$0', $string);

        return \strtolower(\ltrim((string) $snake, '_'));
    }

    /**
     * Convertit une valeur brute de la base de données en valeur typée pour la propriété.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function castValueForProperty(string $property, mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\str_ends_with($property, 'At')) {
            if ($value instanceof \DateTimeImmutable) {
                return $value;
            }
            if (\is_int($value)) {
                return new \DateTimeImmutable()->setTimestamp($value);
            }
            if (\is_string($value) && '' !== $value) {
                return new \DateTimeImmutable($value);
            }

            throw new \RuntimeException('Invalid datetime value for '.$property);
        }

        // Conversion des tinyint/entiers en booléens pour les propriétés booléennes
        if (\str_starts_with($property, 'is') || \str_starts_with($property, 'has')) {
            if (\is_int($value) || \is_string($value)) {
                return (bool) $value;
            }
        }

        return $value;
    }

    /**
     * Extrait les propriétés de l'entité via réflexion et les mappe vers des colonnes snake_case.
     * - DateTimeImmutable => string SQL
     * - ignore les propriétés statiques
     *
     * @param object $entity
     *
     * @return array<string, mixed> (snake_case => value)
     */
    protected function extractData(object $entity): array
    {
        $ref = new \ReflectionClass($entity);
        $data = [];

        // on récupère les propriétés de la classe + parents
        while (false !== $ref) {
            foreach ($ref->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }

                $prop->setAccessible(true);
                $name = $prop->getName();

                // On mappe propriété camelCase -> colonne snake_case
                $column = $this->camelToSnake($name);
                $value = $prop->getValue($entity);

                // DateTimeImmutable -> string SQL
                if ($value instanceof \DateTimeImmutable) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                if (!\array_key_exists($column, $data)) {
                    $data[$column] = $value;
                }
            }

            $ref = $ref->getParentClass();
        }

        return $data;
    }

    /**
     * Si l'entité a setId(), on l'applique après insert.
     *
     * @param object $entity
     * @param int    $id
     *
     * @return void
     */
    protected function applyIdIfPossible(object $entity, int $id): void
    {
        if (\method_exists($entity, 'setId')) {
            $entity->setId($id);
        }
    }
}
