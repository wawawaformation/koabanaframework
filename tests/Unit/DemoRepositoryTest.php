<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Database\BDDFactory;
use Koabana\Model\Repository\DemoRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DemoRepositoryTest extends TestCase
{
    private DemoRepository $repository;

    protected function setUp(): void
    {
        $bddFactory = $this->createMock(BDDFactory::class);
        $this->repository = new DemoRepository($bddFactory);
    }

    public function testFindAllUsersReturnsArray(): void
    {
        $users = $this->repository->findAllUsers();

        self::assertNotEmpty($users);
    }

    public function testFindAllUsersReturnsValidUserStructure(): void
    {
        $users = $this->repository->findAllUsers();

        foreach ($users as $user) {
            self::assertArrayHasKey('id', $user);
            self::assertArrayHasKey('name', $user);
        }
    }

    public function testFindAllUsersContainsExpectedUsers(): void
    {
        $users = $this->repository->findAllUsers();

        self::assertCount(3, $users);

        $names = array_column($users, 'name');
        self::assertContains('Alice', $names);
        self::assertContains('Bob', $names);
        self::assertContains('Charlie', $names);
    }

    public function testUserIdStartsAt1(): void
    {
        $users = $this->repository->findAllUsers();

        self::assertEquals(1, $users[0]['id']);
        self::assertEquals(2, $users[1]['id']);
        self::assertEquals(3, $users[2]['id']);
    }

    public function testUserEmailsAreValid(): void
    {
        $users = $this->repository->findAllUsers();

        self::assertNotEmpty($users);
        // Les utilisateurs de démonstration n'ont pas d'email, juste id et name
    }
}
