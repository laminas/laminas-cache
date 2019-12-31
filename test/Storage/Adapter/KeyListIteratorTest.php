<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\KeyListIterator;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\KeyListIterator
 */
class KeyListIteratorTest extends TestCase
{
    public function testCount()
    {
        $keys = ['key1', 'key2', 'key3'];
        $storage = $this->createMock('Laminas\Cache\Storage\StorageInterface');
        $iterator = new KeyListIterator($storage, $keys);
        $this->assertEquals(3, $iterator->count());
    }
}
