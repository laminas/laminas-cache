<?php

namespace LaminasTest\Cache\Psr\CacheItemPool;

use DateTimeImmutable;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheException;
use Laminas\Cache\Psr\CacheItemPool\CacheItem;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Psr\CacheItemPool\TestAsset\FlushableStorageAdapterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemInterface;
use stdClass;

class CacheItemPoolDecoratorTest extends TestCase
{
    use MockStorageTrait;
    use ProphecyTrait;

    public function testStorageNotFlushableThrowsException()
    {
        $this->expectException(CacheException::class);
        $storage = $this->prophesize(StorageInterface::class);

        $capabilities = new Capabilities($storage->reveal(), new stdClass(), $this->defaultCapabilities);

        $storage->getCapabilities()->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    public function testStorageNeedsSerializerWillThrowException()
    {
        $this->expectException(CacheException::class);
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

    public function testStorageFalseStaticTtlThrowsException()
    {
        $this->expectException(CacheException::class);
        $storage = $this->getStorageProphecy(['staticTtl' => false]);
        $this->getAdapter($storage);
    }

    public function testStorageZeroMinTtlThrowsException()
    {
        $this->expectException(CacheException::class);
        $storage = $this->getStorageProphecy(['staticTtl' => true, 'minTtl' => 0]);
        $this->getAdapter($storage);
    }

    public function testGetDeferredItem()
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
     */
    public function testGetItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getAdapter()->getItem($key);
    }

    public function testGetItemRuntimeExceptionIsMiss()
    {
        $storage = $this->getStorageProphecy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($item->isHit());
    }

    public function testGetItemInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItem('foo');
    }

    public function testGetNonexistentItems()
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

    public function testGetDeferredItems()
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

    public function testGetMixedItems()
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphecy();
        $storage->getItems($keys)
            ->willReturn(['bar' => 'value']);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        $this->assertNull($items['foo']->get());
        $this->assertFalse($items['foo']->isHit());
        $this->assertEquals('value', $items['bar']->get());
        $this->assertTrue($items['bar']->isHit());
    }

    public function testGetItemsInvalidKeyThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->getItems($keys);
    }

    public function testGetItemsRuntimeExceptionIsMiss()
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphecy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        foreach ($keys as $key) {
            $this->assertFalse($items[$key]->isHit());
        }
    }

    public function testGetItemsInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItems(['foo', 'bar']);
    }

    public function testSaveItem()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        $this->assertEquals('bar', $saved['foo']->get());
        $this->assertTrue($saved['foo']->isHit());
    }

    public function testSaveItemWithExpiration()
    {
        $storage = $this->getStorageProphecy()->reveal();
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

    public function testExpiredItemNotSaved()
    {
        $storage = $this->getStorageProphecy()->reveal();
        $adapter = new CacheItemPoolDecorator($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItem('foo');
        $this->assertFalse($saved->isHit());
    }

    public function testSaveForeignItemThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->save($item->reveal());
    }

    public function testSaveItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($adapter->save($item));
    }

    public function testSaveItemInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->save($item);
    }

    public function testHasItemReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasNonexistentItemReturnsFalse()
    {
        $this->assertFalse($this->getAdapter()->hasItem('foo'));
    }

    public function testHasDeferredItemReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasExpiredDeferredItemReturnsFalse()
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
     */
    public function testHasItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getAdapter()->hasItem($key);
    }

    public function testHasItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    public function testHasItemInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->hasItem('foo');
    }

    public function testClearReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->clear());
    }

    public function testClearEmptyReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->clear());
    }

    public function testClearDeferred()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->clear();
        $this->assertFalse($adapter->hasItem('foo'));
    }

    public function testClearRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
        $storage->flush()
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceReturnsTrue()
    {
        $storage = $this->getStorageProphecy(false, ['namespace' => 'laminascache']);
        $storage->clearByNamespace(Argument::any())->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByEmptyNamespaceCallsFlush()
    {
        $storage = $this->getStorageProphecy(false, ['namespace' => '']);
        $storage->flush()->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy(false, ['namespace' => 'laminascache']);
        $storage->clearByNamespace(Argument::any())
            ->willThrow(Exception\RuntimeException::class)
            ->shouldBeCalled();
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testDeleteItemReturnsTrue()
    {
        $storage = $this->getStorageProphecy();
        $storage->removeItems(['foo'])->shouldBeCalled()->willReturn([]);

        $this->assertTrue($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteDeferredItem()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->deleteItem('foo');
        $this->assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testDeleteItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getAdapter()->deleteItem($key);
    }

    public function testDeleteItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteItemInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItem('foo');
    }

    public function testDeleteItemsReturnsTrue()
    {
        $storage = $this->getStorageProphecy();
        $storage->removeItems(['foo', 'bar', 'baz'])->shouldBeCalled()->willReturn([]);

        $this->assertTrue($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteDeferredItems()
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

    public function testDeleteItemsInvalidKeyThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->deleteItems($keys);
    }

    public function testDeleteItemsRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteItemsInvalidArgumentExceptionRethrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->getStorageProphecy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']);
    }

    public function testSaveDeferredReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $this->assertTrue($adapter->saveDeferred($item));
    }

    public function testSaveDeferredForeignItemThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->saveDeferred($item->reveal());
    }

    public function testCommitReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->commit());
    }

    public function testCommitEmptyReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->commit());
    }

    public function testCommitRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphecy();
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

    private function getAdapter(?ObjectProphecy $storage = null): CacheItemPoolDecorator
    {
        if (! $storage) {
            $storage = $this->getStorageProphecy();
        }

        $revealedStorage = $storage->reveal();
        assert($revealedStorage instanceof StorageInterface);
        return new CacheItemPoolDecorator($revealedStorage);
    }

    public function testCanHandleRemoveItemsReturningNonArray()
    {
        $adapter = $this->getStorageProphecy();
        $adapter
            ->removeItems(Argument::type('array'))
            ->willReturn(null);

        $cache = new CacheItemPoolDecorator($adapter->reveal());

        self::assertFalse($cache->deleteItems(['foo']));
    }

    /**
     * @param bool $exists
     * @param bool $sucsessful
     *
     * @dataProvider deletionVerificationProvider
     */
    public function testWillVerifyKeyExistenceByUsingHasItemsWhenDeletionWasNotSuccessful($exists, $sucsessful)
    {
        $adapter = $this->getStorageProphecy();
        $adapter
            ->removeItems(Argument::type('array'))
            ->willReturn(['foo']);

        $adapter
            ->hasItems(Argument::exact(['foo']))
            ->willReturn(['foo' => $exists]);

        $cache = new CacheItemPoolDecorator($adapter->reveal());

        self::assertEquals($sucsessful, $cache->deleteItems(['foo']));
    }

    public function testWontSaveAlreadyExpiredCacheItemAsDeferredItem(): void
    {
        $adapter = $this->createMock(FlushableStorageAdapterInterface::class);
        $adapter
            ->expects(self::exactly(2))
            ->method('getCapabilities')
            ->willReturn(new Capabilities($adapter, new stdClass(), $this->defaultCapabilities));

        $adapter
            ->expects(self::never())
            ->method('removeItems');
        $adapter
            ->expects(self::never())
            ->method('setItem');

        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        $item = new CacheItem('foo', 'bar', false);
        $item->expiresAt(DateTimeImmutable::createFromFormat('U', time() - 1));

        $cache = new CacheItemPoolDecorator($adapter);
        $cache->saveDeferred($item);

        self::assertFalse($item->isHit());
        self::assertFalse($cache->hasItem($item->getKey()));
    }

    public function deletionVerificationProvider()
    {
        return [
            'deletion failed due to hasItems states the key still exists' => [true, false],
            'deletion successful due to hasItems states the key does not exist' => [false, true],
        ];
    }
}
