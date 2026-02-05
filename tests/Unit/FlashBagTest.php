<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\SessionBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FlashBagTest extends TestCase
{
    private FlashBag $flashBag;

    /** @var array<string, mixed> */
    private array $sessionData = [];

    protected function setUp(): void
    {
        $session = new SessionBag($this->sessionData);
        $this->flashBag = new FlashBag($session);
    }

    public function testAddAndGet(): void
    {
        $this->flashBag->add('success', 'Operation succeeded');
        $messages = $this->flashBag->get('success');
        self::assertEquals(['Operation succeeded'], $messages);
    }

    public function testAddMultipleMessages(): void
    {
        $this->flashBag->add('success', 'First message');
        $this->flashBag->add('success', 'Second message');
        $messages = $this->flashBag->get('success');
        self::assertEquals(['First message', 'Second message'], $messages);
    }

    public function testGetClearsMessages(): void
    {
        $this->flashBag->add('success', 'Message');
        $this->flashBag->get('success');

        $messages = $this->flashBag->get('success');
        self::assertEquals([], $messages);
    }

    public function testAddMultipleTypes(): void
    {
        $this->flashBag->add('success', 'Success message');
        $this->flashBag->add('error', 'Error message');
        $this->flashBag->add('info', 'Info message');

        $all = $this->flashBag->all();
        self::assertArrayHasKey('success', $all);
        self::assertArrayHasKey('error', $all);
        self::assertArrayHasKey('info', $all);
    }

    public function testAllClearsAll(): void
    {
        $this->flashBag->add('success', 'Message 1');
        $this->flashBag->add('error', 'Message 2');

        $this->flashBag->all();

        $all = $this->flashBag->all();
        self::assertEquals([], $all);
    }

    public function testHas(): void
    {
        $this->flashBag->add('success', 'Message');
        self::assertTrue($this->flashBag->has('success'));
        self::assertFalse($this->flashBag->has('non_existent'));
    }

    public function testHasAnyType(): void
    {
        $this->flashBag->add('success', 'Message');
        self::assertTrue($this->flashBag->has());
    }

    public function testHasAnyTypeWhenEmpty(): void
    {
        self::assertFalse($this->flashBag->has());
    }

    public function testClear(): void
    {
        $this->flashBag->add('success', 'Message');
        $this->flashBag->clear();

        self::assertFalse($this->flashBag->has('success'));
    }

    public function testGetEmptyTypeReturnsEmptyArray(): void
    {
        $messages = $this->flashBag->get('non_existent');
        self::assertEquals([], $messages);
    }
}
