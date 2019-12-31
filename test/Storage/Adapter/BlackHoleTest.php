<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\BlackHole;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test case
 */

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Blackhole<extended>
 */
class BlackHoleTest extends TestCase
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

    /**
     * A data provider for common storage adapter names
     */
    public function getCommonAdapterNamesProvider()
    {
        return [
            ['black_hole'],
            ['blackhole'],
            ['blackHole'],
            ['BlackHole'],
        ];
    }

    /**
     * @dataProvider getCommonAdapterNamesProvider
     */
    public function testAdapterPluginManagerWithCommonNames($commonAdapterName)
    {
        $pluginManager = new AdapterPluginManager(new ServiceManager);
        $this->assertTrue(
            $pluginManager->has($commonAdapterName),
            "Storage adapter name '{$commonAdapterName}' not found in storage adapter plugin manager"
        );
    }

    public function testGetOptions()
    {
        $options = $this->storage->getOptions();
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\AdapterOptions', $options);
    }

    public function testSetOptions()
    {
        $this->storage->setOptions(['namespace' => 'test']);
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
        $this->assertSame(['test'], $this->storage->setItems(['test' => 1]));
        $this->assertSame(['test'], $this->storage->addItems(['test' => 1]));
        $this->assertSame(['test'], $this->storage->replaceItems(['test' => 1]));
        $this->assertSame(['test'], $this->storage->touchItems(['test']));
        $this->assertSame([], $this->storage->incrementItems(['test' => 1]));
        $this->assertSame([], $this->storage->decrementItems(['test' => 1]));
        $this->assertSame([], $this->storage->hasItems(['test']));
        $this->assertSame([], $this->storage->getItems(['test']));
        $this->assertSame([], $this->storage->getMetadatas(['test']));
        $this->assertSame(['test'], $this->storage->removeItems(['test']));
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
        $this->assertFalse($this->storage->setTags('test', ['tag1']));
        $this->assertFalse($this->storage->getTags('test'));
        $this->assertFalse($this->storage->clearByTags(['tag1']));
    }

    public function testTotalSpaceCapableInterface()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\TotalSpaceCapableInterface', $this->storage);
        $this->assertSame(0, $this->storage->getTotalSpace());
    }
}
