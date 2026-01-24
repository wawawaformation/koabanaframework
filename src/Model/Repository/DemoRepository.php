<?php

namespace Koabana\Model\Repository;

final class DemoRepository extends AbstractRepository
{
    public function findAll(): array
    {
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->query('SELECT * FROM users');
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $users;
    }
}
