<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\KeyListIterator;
use PHPUnit\Framework\TestCase;
use Laminas\Cache\Storage\StorageInterface;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Adapter\KeyListIterator
 */
class KeyListIteratorTest extends TestCase
{
    public function testCount(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $storage = $this->createMock(StorageInterface::class);
        $iterator = new KeyListIterator($storage, $keys);
        self::assertEquals(3, $iterator->count());
    }
}
