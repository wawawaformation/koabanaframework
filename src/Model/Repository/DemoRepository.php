<?php

declare(strict_types=1);

namespace Koabana\Model\Repository;

/**
 * Repository de démonstration (données en mémoire).
 */
final class DemoRepository extends AbstractRepository
{
    /**
     * Summary of findAllUsers
     *
     * @return array{id: int, name: string}[]
     */
    public function findAllUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ];
    }
}
