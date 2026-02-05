<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Model\Entity\AbstractEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AbstractEntityTest extends TestCase
{
    public function testToArrayReturnsAllPropertiesIncludingChildAttributes(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-02 11:30:00');
        $birthday = new \DateTimeImmutable('1990-05-06 07:08:09');

        $entity = new DummyEntity();
        $entity->setId(1)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
            ->setName('Alice')
            ->setAge(30)
            ->setBirthday($birthday)
        ;

        $data = $entity->toArray();

        self::assertSame(1, $data['id']);
        self::assertSame('2024-01-01 10:00:00', $data['createdAt']);
        self::assertSame('2024-01-02 11:30:00', $data['updatedAt']);

        self::assertSame('Alice', $data['name']);
        self::assertSame(30, $data['age']);
        self::assertSame('1990-05-06 07:08:09', $data['birthday']);

        self::assertArrayHasKey('nickname', $data);
        self::assertNull($data['nickname']);

        self::assertArrayNotHasKey('notInitialized', $data);

        self::assertSame('Alice', $entity->getName());
        self::assertSame(30, $entity->getAge());
        self::assertNull($entity->getNickname());
        self::assertSame($birthday, $entity->getBirthday());
    }

    public function testHydrateUpdatesPropertiesWhenSettersExist(): void
    {
        $entity = new DummyEntity();
        $entity->setId(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01 10:00:00'))
            ->setUpdatedAt(new \DateTimeImmutable('2024-01-02 11:30:00'))
            ->setName('Alice')
            ->setAge(30)
            ->setBirthday(new \DateTimeImmutable('1990-05-06 07:08:09'))
        ;

        $entity->hydrate([
            'name' => 'Bob',
            'age' => 40,
            'nickname' => 'Bobby',
            'birthday' => new \DateTimeImmutable('2000-02-03 04:05:06'),
            'updatedAt' => new \DateTimeImmutable('2024-02-02 12:00:00'),
        ]);

        $data = $entity->toArray();

        self::assertSame('Bob', $data['name']);
        self::assertSame(40, $data['age']);
        self::assertSame('Bobby', $data['nickname']);
        self::assertSame('2000-02-03 04:05:06', $data['birthday']);
        self::assertSame('2024-02-02 12:00:00', $data['updatedAt']);

        self::assertSame('Bob', $entity->getName());
        self::assertSame(40, $entity->getAge());
        self::assertSame('Bobby', $entity->getNickname());
    }
}

final class DummyEntity extends AbstractEntity
{
    private string $name = '';

    private int $age = 0;

    private ?string $nickname = null;

    private \DateTimeImmutable $birthday;

    /**
     * @phpstan-ignore-next-line
     */
    private string $notInitialized;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setBirthday(\DateTimeImmutable $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): \DateTimeImmutable
    {
        return $this->birthday;
    }
}
