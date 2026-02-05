<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use PHPUnit\Framework\TestCase;

final class ProfileBagTest extends TestCase
{
    private ProfileBag $profileBag;
    /** @var array<string, mixed> */
    private array $sessionData = [];

    protected function setUp(): void
    {
        $session = new SessionBag($this->sessionData);
        $this->profileBag = new ProfileBag($session);
    }

    public function testIsLoggedWhenEmpty(): void
    {
        self::assertFalse($this->profileBag->isLogged());
    }

    public function testSetProfileData(): void
    {
        $this->profileBag->set([
            'user_id' => 1,
            'user_firstname' => 'John',
            'user_email' => 'john@example.com',
        ]);

        self::assertTrue($this->profileBag->isLogged());
    }

    public function testGetId(): void
    {
        $this->profileBag->set(['user_id' => 42]);
        self::assertEquals(42, $this->profileBag->getId());
    }

    public function testGetIdWhenNotSet(): void
    {
        self::assertNull($this->profileBag->getId());
    }

    public function testGetFirstname(): void
    {
        $this->profileBag->set(['user_firstname' => 'Alice']);
        self::assertEquals('Alice', $this->profileBag->getFirstname());
    }

    public function testGetFirstnameWhenNotSet(): void
    {
        self::assertEquals('', $this->profileBag->getFirstname());
    }

    public function testGetEmail(): void
    {
        $this->profileBag->set(['user_email' => 'test@example.com']);
        self::assertEquals('test@example.com', $this->profileBag->getEmail());
    }

    public function testGetEmailWhenNotSet(): void
    {
        self::assertEquals('', $this->profileBag->getEmail());
    }

    public function testToArray(): void
    {
        $data = [
            'user_id' => 1,
            'user_firstname' => 'Bob',
            'user_email' => 'bob@example.com',
        ];
        $this->profileBag->set($data);

        self::assertEquals($data, $this->profileBag->toArray());
    }

    public function testClear(): void
    {
        $this->profileBag->set([
            'user_id' => 1,
            'user_firstname' => 'John',
            'user_email' => 'john@example.com',
        ]);

        $this->profileBag->clear();

        self::assertFalse($this->profileBag->isLogged());
        self::assertNull($this->profileBag->getId());
        self::assertEquals('', $this->profileBag->getFirstname());
        self::assertEquals('', $this->profileBag->getEmail());
    }

    public function testCompleteUserProfile(): void
    {
        $this->profileBag->set([
            'user_id' => 123,
            'user_firstname' => 'Charlie',
            'user_email' => 'charlie@example.com',
        ]);

        self::assertTrue($this->profileBag->isLogged());
        self::assertEquals(123, $this->profileBag->getId());
        self::assertEquals('Charlie', $this->profileBag->getFirstname());
        self::assertEquals('charlie@example.com', $this->profileBag->getEmail());
    }

    public function testPartialUserProfile(): void
    {
        $this->profileBag->set(['user_id' => 999]);

        self::assertTrue($this->profileBag->isLogged());
        self::assertEquals(999, $this->profileBag->getId());
        self::assertEquals('', $this->profileBag->getFirstname());
        self::assertEquals('', $this->profileBag->getEmail());
    }
}
