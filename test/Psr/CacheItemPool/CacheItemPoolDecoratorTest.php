<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use stdClass;

class CacheItemPoolDecoratorTest extends TestCase
{
    use MockStorageTrait;

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testStorageNotFlushableThrowsException(): void
    {
        $storage = $this->prophesize(StorageInterface::class);

        $capabilities = new Capabilities($storage->reveal(), new stdClass(), $this->defaultCapabilities);

        $storage->getCapabilities()->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testStorageNeedsSerializerWillThrowException(): void
    {
        $storage = $this->prophesize(StorageInterface::class);

        $dataTypes = [
            'staticTtl' => true,
            'minTtl' => 1,
            'supportedDatatypes' => [
                'NULL'     => true,
                'boolean'  => true,
                'integer'  => true,
                'double'   => false,
                'string'   => true,
                'array'    => true,
                'object'   => 'object',
                'resource' => false,
            ],
        ];
        $capabilities = new Capabilities($storage->reveal(), new stdClass(), $dataTypes);

        $storage->getCapabilities()->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testStorageFalseStaticTtlThrowsException(): void
    {
        $storage = $this->getStorageProphesy(['staticTtl' => false]);
        $this->getAdapter($storage);
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testStorageZeroMinTtlThrowsException(): void
    {
        $storage = $this->getStorageProphesy(['staticTtl' => true, 'minTtl' => 0]);
        $this->getAdapter($storage);
    }

    public function testGetDeferredItem(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->saveDeferred($item);
        $item = $adapter->getItem('foo');
        $this->assertTrue($item->isHit());
        $this->assertEquals('bar', $item->get());
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testGetItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->getItem($key);
    }

    public function testGetItemRuntimeExceptionIsMiss(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($item->isHit());
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testGetItemInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItem('foo');
    }

    public function testGetNonexistentItems(): void
    {
        $keys = ['foo', 'bar'];
        $adapter = $this->getAdapter();
        $items = $adapter->getItems($keys);
        $this->assertEquals($keys, array_keys($items));
        foreach ($keys as $key) {
            $this->assertEquals($key, $items[$key]->getKey());
        }
        foreach ($items as $item) {
            $this->assertNull($item->get());
            $this->assertFalse($item->isHit());
        }
    }

    public function testGetDeferredItems(): void
    {
        $keys = ['foo', 'bar'];
        $adapter = $this->getAdapter();
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            $item->set('baz');
            $adapter->saveDeferred($item);
        }
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            $this->assertTrue($item->isHit());
        }
    }

    public function testGetMixedItems(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphesy();
        $storage->getItems($keys)
            ->willReturn(['bar' => 'value']);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        $this->assertNull($items['foo']->get());
        $this->assertFalse($items['foo']->isHit());
        $this->assertEquals('value', $items['bar']->get());
        $this->assertTrue($items['bar']->isHit());
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testGetItemsInvalidKeyThrowsException(): void
    {
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->getItems($keys);
    }

    public function testGetItemsRuntimeExceptionIsMiss(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphesy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        foreach ($keys as $key) {
            $this->assertFalse($items[$key]->isHit());
        }
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testGetItemsInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItems(['foo', 'bar']);
    }

    public function testSaveItem(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        $this->assertEquals('bar', $saved['foo']->get());
        $this->assertTrue($saved['foo']->isHit());
    }

    public function testSaveItemWithExpiration(): void
    {
        $storage = $this->getStorageProphesy()->reveal();
        $adapter = new CacheItemPoolDecorator($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(3600);
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        $this->assertEquals('bar', $saved['foo']->get());
        $this->assertTrue($saved['foo']->isHit());
        // ensure original TTL not modified
        $options = $storage->getOptions();
        $this->assertEquals(0, $options->getTtl());
    }

    public function testExpiredItemNotSaved(): void
    {
        $storage = $this->getStorageProphesy()->reveal();
        $adapter = new CacheItemPoolDecorator($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItem('foo');
        $this->assertFalse($saved->isHit());
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testSaveForeignItemThrowsException(): void
    {
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->save($item->reveal());
    }

    public function testSaveItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($adapter->save($item));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testSaveItemInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->save($item);
    }

    public function testHasItemReturnsTrue(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasNonexistentItemReturnsFalse(): void
    {
        $this->assertFalse($this->getAdapter()->hasItem('foo'));
    }

    public function testHasDeferredItemReturnsTrue(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasExpiredDeferredItemReturnsFalse(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $adapter->saveDeferred($item);
        $this->assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testHasItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->hasItem($key);
    }

    public function testHasItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testHasItemInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->hasItem('foo');
    }

    public function testClearReturnsTrue(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->clear());
    }

    public function testClearEmptyReturnsTrue(): void
    {
        $this->assertTrue($this->getAdapter()->clear());
    }

    public function testClearDeferred(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->clear();
        $this->assertFalse($adapter->hasItem('foo'));
    }

    public function testClearRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->flush()
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceReturnsTrue(): void
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => 'laminascache']);
        $storage->clearByNamespace(Argument::any())->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByEmptyNamespaceCallsFlush(): void
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => '']);
        $storage->flush()->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => 'laminascache']);
        $storage->clearByNamespace(Argument::any())
            ->willThrow(Exception\RuntimeException::class)
            ->shouldBeCalled();
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testDeleteItemReturnsTrue(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(['foo'])->shouldBeCalled()->willReturn(['foo']);

        $this->assertTrue($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteDeferredItem(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->deleteItem('foo');
        $this->assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testDeleteItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->deleteItem($key);
    }

    public function testDeleteItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItem('foo'));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testDeleteItemInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItem('foo');
    }

    public function testDeleteItemsReturnsTrue(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(['foo', 'bar', 'baz'])->shouldBeCalled()->willReturn(['foo']);

        $this->assertTrue($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteDeferredItems(): void
    {
        $keys = ['foo', 'bar', 'baz'];
        $adapter = $this->getAdapter();
        foreach ($keys as $key) {
            $item = $adapter->getItem($key);
            $adapter->saveDeferred($item);
        }
        $keys = ['foo', 'bar'];
        $adapter->deleteItems($keys);
        foreach ($keys as $key) {
            $this->assertFalse($adapter->hasItem($key));
        }
        $this->assertTrue($adapter->hasItem('baz'));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testDeleteItemsInvalidKeyThrowsException(): void
    {
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->deleteItems($keys);
    }

    public function testDeleteItemsRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testDeleteItemsInvalidArgumentExceptionRethrown(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']);
    }

    public function testSaveDeferredReturnsTrue(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $this->assertTrue($adapter->saveDeferred($item));
    }

    /**
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException
     */
    public function testSaveDeferredForeignItemThrowsException(): void
    {
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->saveDeferred($item->reveal());
    }

    public function testCommitReturnsTrue(): void
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->commit());
    }

    public function testCommitEmptyReturnsTrue(): void
    {
        $this->assertTrue($this->getAdapter()->commit());
    }

    public function testCommitRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertFalse($adapter->commit());
    }

    public function invalidKeyProvider()
    {
        return array_map(function ($v) {
            return [$v];
        }, $this->getInvalidKeys());
    }

    private function getInvalidKeys()
    {
        return [
            'key{',
            'key}',
            'key(',
            'key)',
            'key/',
            'key\\',
            'key@',
            'key:',
            new stdClass()
        ];
    }

    /**
     * @param Prophesy $storage
     * @return CacheItemPoolDecorator
     */
    private function getAdapter($storage = null)
    {
        if (! $storage) {
            $storage = $this->getStorageProphesy();
        }
        return new CacheItemPoolDecorator($storage->reveal());
    }
}
