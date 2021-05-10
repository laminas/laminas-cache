<?php

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\KeyListIterator;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Adapter\KeyListIterator
 */
class KeyListIteratorTest extends TestCase
{
    public function testCount(): void
    {
        $keys     = ['key1', 'key2', 'key3'];
        $storage  = $this->createMock(StorageInterface::class);
        $iterator = new KeyListIterator($storage, $keys);
        self::assertEquals(3, $iterator->count());
    }
}
