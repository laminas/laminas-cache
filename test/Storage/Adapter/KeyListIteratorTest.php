<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\KeyListIterator;
use Laminas\Cache\Storage\Adapter\Memory as MemoryStorage;

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 */
class KeyListIteratorTest extends \PHPUnit_Framework_TestCase
{

    public function testCount()
    {
        $keys = array('key1', 'key2', 'key3');
        $storage = $this->getMock('Laminas\Cache\Storage\StorageInterface');
        $iterator = new KeyListIterator($storage, $keys);
        $this->assertEquals(3, $iterator->count());
    }
}
