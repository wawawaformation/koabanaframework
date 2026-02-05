<?php

declare(strict_types=1);

namespace Koabana\Model\Repository;

use Koabana\Model\Entity\TestEntity;

/**
 * Repository pour l'entité TestEntity.
 */
class TestRepository extends AbstractRepository
{
    protected string $table = 'test';
    protected string $entityClass = TestEntity::class;
}
