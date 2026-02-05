<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Http\Session\SessionBag;
use PHPUnit\Framework\TestCase;

final class SessionBagTest extends TestCase
{
    private SessionBag $sessionBag;
    /** @var array<string, mixed> */
    private array $data = [];

    protected function setUp(): void
    {
        $this->sessionBag = new SessionBag($this->data);
    }

    public function testSetAndGet(): void
    {
        $this->sessionBag->set('key', 'value');
        self::assertEquals('value', $this->sessionBag->get('key'));
    }

    public function testGetWithDefault(): void
    {
        self::assertEquals('default', $this->sessionBag->get('non_existent', 'default'));
    }

    public function testGetWithoutDefaultReturnsNull(): void
    {
        self::assertNull($this->sessionBag->get('non_existent'));
    }

    public function testHas(): void
    {
        $this->sessionBag->set('key', 'value');
        self::assertTrue($this->sessionBag->has('key'));
        self::assertFalse($this->sessionBag->has('non_existent'));
    }

    public function testRemove(): void
    {
        $this->sessionBag->set('key', 'value');
        self::assertTrue($this->sessionBag->has('key'));
        
        $this->sessionBag->remove('key');
        self::assertFalse($this->sessionBag->has('key'));
    }

    public function testAll(): void
    {
        $this->sessionBag->set('key1', 'value1');
        $this->sessionBag->set('key2', 'value2');
        
        $all = $this->sessionBag->all();
        self::assertEquals(['key1' => 'value1', 'key2' => 'value2'], $all);
    }

    public function testClear(): void
    {
        $this->sessionBag->set('key1', 'value1');
        $this->sessionBag->set('key2', 'value2');
        
        $this->sessionBag->clear();
        self::assertEquals([], $this->sessionBag->all());
    }

    public function testBag(): void
    {
        $this->sessionBag->set('bag_key', ['nested' => 'value']);
        $bagData = $this->sessionBag->bag('bag_key');
        self::assertInstanceOf(SessionBag::class, $bagData);
        self::assertEquals(['nested' => 'value'], $bagData->all());
    }

    public function testBagWithDefault(): void
    {
        $result = $this->sessionBag->bag('non_existent');
        self::assertInstanceOf(SessionBag::class, $result);
        self::assertEquals([], $result->all());
    }

    public function testSetMultipleTypes(): void
    {
        $this->sessionBag->set('string', 'value');
        $this->sessionBag->set('int', 42);
        $this->sessionBag->set('array', ['a' => 'b']);
        $this->sessionBag->set('bool', true);
        
        self::assertEquals('value', $this->sessionBag->get('string'));
        self::assertEquals(42, $this->sessionBag->get('int'));
        self::assertEquals(['a' => 'b'], $this->sessionBag->get('array'));
        self::assertTrue($this->sessionBag->get('bool'));
    }
}
