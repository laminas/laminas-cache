<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\BlackHole;
use Laminas\Cache\Storage\AvailableSpaceCapableInterface;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\ClearByPrefixInterface;
use Laminas\Cache\Storage\ClearExpiredInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\IterableInterface;
use Laminas\Cache\Storage\OptimizableInterface;
use Laminas\Cache\Storage\TaggableInterface;
use Laminas\Cache\Storage\TotalSpaceCapableInterface;
use Laminas\Cache\StorageFactory;

/**
 * PHPUnit test case
 */

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 */
class BlackHoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The storage adapter
     *
     * @var StorageInterface
     */
    protected $storage;

    public function setUp()
    {
        $this->storage = StorageFactory::adapterFactory('BlackHole');
    }

    public function testGetOptions()
    {
        $options = $this->storage->getOptions();
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\AdapterOptions', $options);
    }

    public function testSetOptions()
    {
        $this->storage->setOptions(array('namespace' => 'test'));
        $this->assertSame('test', $this->storage->getOptions()->getNamespace());
    }

    public function testGetCapabilities()
    {
        $capabilities = $this->storage->getCapabilities();
        $this->assertInstanceOf('Laminas\Cache\Storage\Capabilities', $capabilities);
    }

    public function testSingleStorageOperatios()
    {
        $this->assertFalse($this->storage->setItem('test', 1));
        $this->assertFalse($this->storage->addItem('test', 1));
        $this->assertFalse($this->storage->replaceItem('test', 1));
        $this->assertFalse($this->storage->touchItem('test'));
        $this->assertFalse($this->storage->incrementItem('test', 1));
        $this->assertFalse($this->storage->decrementItem('test', 1));
        $this->assertFalse($this->storage->hasItem('test'));
        $this->assertNull($this->storage->getItem('test', $success));
        $this->assertFalse($success);
        $this->assertFalse($this->storage->getMetadata('test'));
        $this->assertFalse($this->storage->removeItem('test'));
    }

    public function testMultiStorageOperatios()
    {
        $this->assertSame(array('test'), $this->storage->setItems(array('test' => 1)));
        $this->assertSame(array('test'), $this->storage->addItems(array('test' => 1)));
        $this->assertSame(array('test'), $this->storage->replaceItems(array('test' => 1)));
        $this->assertSame(array('test'), $this->storage->touchItems(array('test')));
        $this->assertSame(array(), $this->storage->incrementItems(array('test' => 1)));
        $this->assertSame(array(), $this->storage->decrementItems(array('test' => 1)));
        $this->assertSame(array(), $this->storage->hasItems(array('test')));
        $this->assertSame(array(), $this->storage->getItems(array('test')));
        $this->assertSame(array(), $this->storage->getMetadatas(array('test')));
        $this->assertSame(array('test'), $this->storage->removeItems(array('test')));
    }

    public function testAvailableSpaceCapableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\AvailableSpaceCapableInterface', $this->storage);
        $this->assertSame(0, $this->storage->getAvailableSpace());
    }

    public function testClearByNamespaceInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\ClearByNamespaceInterface', $this->storage);
        $this->assertFalse($this->storage->clearByNamespace('test'));
    }

    public function testClearByPrefixInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\ClearByPrefixInterface', $this->storage);
        $this->assertFalse($this->storage->clearByPrefix('test'));
    }

    public function testCleariExpiredInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\ClearExpiredInterface', $this->storage);
        $this->assertFalse($this->storage->clearExpired());
    }

    public function testFlushableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\FlushableInterface', $this->storage);
        $this->assertFalse($this->storage->flush());
    }

    public function testIterableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\IterableInterface', $this->storage);
        $iterator = $this->storage->getIterator();
        foreach ($iterator as $item) {
            $this->fail('The iterator of the BlackHole adapter should be empty');
        }
    }

    public function testOptimizableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\OptimizableInterface', $this->storage);
        $this->assertFalse($this->storage->optimize());
    }

    public function testTaggableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\TaggableInterface', $this->storage);
        $this->assertFalse($this->storage->setTags('test', array('tag1')));
        $this->assertFalse($this->storage->getTags('test'));
        $this->assertFalse($this->storage->clearByTags(array('tag1')));
    }

    public function testTotalSpaceCapableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\TotalSpaceCapableInterface', $this->storage);
        $this->assertSame(0, $this->storage->getTotalSpace());
    }
}
