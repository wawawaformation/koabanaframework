<?php declare(strict_types=1);

namespace Koabana\Database;

/**
 * Mini Query Builder simple et minimaliste.
 * 
 * Permet construire des requêtes SELECT de façon fluide sans ORM.
 * Reste compatible avec les prepared statements PDO.
 */
final class QueryBuilder
{
    private string $table;
    private BDDFactory $bddFactory;
    /** @var array<string> */
    private array $columns = [];
    /** @var array<array{column: string, operator: string, value: mixed}> */
    private array $wheres = [];
    /** @var array<array{column: string, direction: string}> */
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    /** @var array<mixed> */
    private array $bindings = [];

    public function __construct(string $table, BDDFactory $bddFactory)
    {
        $this->table = $table;
        $this->bddFactory = $bddFactory;
    }

    /**
     * Sélectionne des colonnes spécifiques (par défaut: *)
     * @param array<string>|string $columns
     */
    public function select(array|string $columns = '*'): self
    {
        if (\is_string($columns)) {
            $this->columns = [$columns];
        } else {
            $this->columns = $columns;
        }
        return $this;
    }

    /**
     * Ajoute une condition WHERE
     * @param string $column Nom de la colonne
     * @param string $operator Opérateur (=, <, >, <=, >=, !=, LIKE, etc.)
     * @param mixed $value Valeur à comparer
     */
    public function where(string $column, string $operator = '=', mixed $value = null): self
    {
        // Gestion du cas where('column', value) sans opérateur
        if ($value === null && $operator !== '=') {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec IN
     * @param string $column
     * @param array<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = \array_map(fn () => '?', $values);
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => '(' . \implode(', ', $placeholders) . ')',
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec BETWEEN
     * @param string $column
     * @param mixed $min
     * @param mixed $max
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => ['min' => $min, 'max' => $max],
        ];

        return $this;
    }

    /**
     * Tri des résultats
     * @param string $column Colonne
     * @param string $direction ASC ou DESC
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = \strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Invalid order direction: ' . $direction);
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Limite le nombre de résultats
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Offset (pagination)
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Construit et exécute la requête SELECT, retourne les résultats
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = $this->toSql();
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->prepare($sql);

        // Binding des paramètres
        $index = 1;
        foreach ($this->wheres as $where) {
            if ($where['operator'] === 'IN' || $where['operator'] === 'BETWEEN') {
                continue;
            }
            $stmt->bindValue($index++, $where['value']);
        }

        // Binding des valeurs from whereIn/whereBetween
        foreach ($this->bindings as $binding) {
            $stmt->bindValue($index++, $binding);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compte les résultats
     */
    public function count(): int
    {
        $sql = $this->buildCountSql();
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->prepare($sql);

        $index = 1;
        foreach ($this->wheres as $where) {
            if ($where['operator'] === 'IN' || $where['operator'] === 'BETWEEN') {
                continue;
            }
            $stmt->bindValue($index++, $where['value']);
        }

        foreach ($this->bindings as $binding) {
            $stmt->bindValue($index++, $binding);
        }

        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Retourne la première ligne ou null
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Construit la requête SQL
     */
    private function toSql(): string
    {
        $columns = empty($this->columns) ? '*' : \implode(', ', $this->columns);
        $sql = 'SELECT ' . $columns . ' FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClauses();
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . $this->buildOrderClauses();
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * Construit les clauses WHERE
     */
    private function buildWhereClauses(): string
    {
        $clauses = [];

        foreach ($this->wheres as $where) {
            $column = $where['column'];
            $operator = $where['operator'];

            if ($operator === 'IN') {
                $clauses[] = $column . ' ' . $where['value'];
            } elseif ($operator === 'BETWEEN') {
                $clauses[] = $column . ' BETWEEN ? AND ?';
                // Les valeurs seront bindées séparément
            } else {
                $clauses[] = $column . ' ' . $operator . ' ?';
            }
        }

        return \implode(' AND ', $clauses);
    }

    /**
     * Construit les clauses ORDER BY
     */
    private function buildOrderClauses(): string
    {
        return \implode(', ', \array_map(
            fn ($order) => $order['column'] . ' ' . $order['direction'],
            $this->orders
        ));
    }

    /**
     * Construit une requête COUNT
     */
    private function buildCountSql(): string
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClauses();
        }

        return $sql;
    }

    /**
     * Retourne la requête SQL générée (utile pour debugging)
     */
    public function toRawSql(): string
    {
        return $this->toSql();
    }
}
