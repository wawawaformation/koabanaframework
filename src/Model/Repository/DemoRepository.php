<?php

declare(strict_types=1);

namespace Koabana\Model\Repository;

final class DemoRepository extends AbstractRepository
{
    /**
     * Summary of findAll
     *
     * @return array <int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->query('SELECT * FROM users');

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
