<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheException;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventManager;
use LaminasTest\Cache\Psr\CacheItemPool\TestAsset\StorageAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use stdClass;

final class CacheItemPoolDecoratorTest extends TestCase
{
    protected $defaultCapabilities = [
        'staticTtl' => true,
        'minTtl' => 1,
        'supportedDatatypes' => [
            'NULL'     => true,
            'boolean'  => true,
            'integer'  => true,
            'double'   => true,
            'string'   => true,
            'array'    => true,
            'object'   => 'object',
            'resource' => false,
        ],
    ];

    /**
     * @var (Capabilities&MockObject)|null
     */
    private $capabilitiesMock;

    /**
     * @var (AdapterOptions&MockObject)|null
     */
    private $optionsMock;

    /**
     * @return StorageInterface&MockObject
     */
    private function createMockedStorage(
        array $capabilities = null,
        array $options = null
    ): StorageInterface {

        $storage = $this->getMockBuilder(StorageAdapter::class)
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $storage
            ->method('getEventManager')
            ->willReturn(new EventManager());

        $capabilities = $this->createCapabilitiesMock(
            $storage,
            $capabilities ?? $this->defaultCapabilities
        );

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $storage
            ->method('getOptions')
            ->willReturn($this->createOptionsMock($options));

        return $storage;
    }

    public function testStorageNotFlushableThrowsException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMock(StorageInterface::class);

        $capabilities = new Capabilities($storage, new stdClass(), $this->defaultCapabilities);

        $storage
            ->expects(self::once())
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    public function testStorageNeedsSerializerWillThrowException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMock(StorageInterface::class);

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
        $capabilities = new Capabilities($storage, new stdClass(), $dataTypes);

        $storage
            ->expects(self::once())
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    public function testStorageFalseStaticTtlThrowsException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMockedStorage(['staticTtl' => false]);
        $this->getAdapter($storage);
    }

    public function testStorageZeroMinTtlThrowsException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMockedStorage(['staticTtl' => true, 'minTtl' => 0]);
        $this->getAdapter($storage);
    }

    public function testGetDeferredItem(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->willReturnOnConsecutiveCalls(null);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->saveDeferred($item);
        $item = $adapter->getItem('foo');
        self::assertTrue($item->isHit());
        self::assertEquals('bar', $item->get());
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testGetItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $this->getAdapter($storage)->getItem($key);
    }

    public function testGetItemRuntimeExceptionIsMiss(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItem')
            ->willThrowException(new Exception\RuntimeException());

        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        self::assertFalse($item->isHit());
    }

    public function testGetItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItem')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->getItem('foo');
    }

    public function testGetNonexistentItems(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([]);

        $adapter = $this->getAdapter($storage);
        $items = $adapter->getItems($keys);
        self::assertEquals($keys, array_keys($items));
        foreach ($keys as $key) {
            self::assertEquals($key, $items[$key]->getKey());
        }
        foreach ($items as $item) {
            self::assertNull($item->get());
            self::assertFalse($item->isHit());
        }
    }

    public function testGetDeferredItems(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([]);

        $adapter = $this->getAdapter($storage);
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            $item->set('baz');
            $adapter->saveDeferred($item);
        }
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            self::assertTrue($item->isHit());
        }
    }

    public function testGetMixedItems(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->createMockedStorage();

        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willReturn(['bar' => 'value']);

        $items = $this->getAdapter($storage)->getItems($keys);
        self::assertCount(2, $items);
        self::assertNull($items['foo']->get());
        self::assertFalse($items['foo']->isHit());
        self::assertEquals('value', $items['bar']->get());
        self::assertTrue($items['bar']->isHit());
    }

    public function testGetItemsInvalidKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter($this->createMockedStorage())->getItems($keys);
    }

    public function testGetItemsRuntimeExceptionIsMiss(): void
    {
        $keys = ['foo', 'bar'];
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willThrowException(new Exception\RuntimeException());

        $items = $this->getAdapter($storage)->getItems($keys);
        self::assertCount(2, $items);
        foreach ($keys as $key) {
            self::assertFalse($items[$key]->isHit());
        }
    }

    public function testGetItemsInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->getItems(['foo', 'bar']);
    }

    public function testSaveItem(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->wilLReturn(null);

        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with(['foo'])
            ->willReturn(['foo' => 'bar']);

        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', 'bar')
            ->willReturn(true);

        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        self::assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        self::assertEquals('bar', $saved['foo']->get());
        self::assertTrue($saved['foo']->isHit());
    }

    public function testSaveItemWithExpiration(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        assert($this->optionsMock instanceof MockObject);
        $this->optionsMock
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([3600], [0])
            ->willReturnSelf();

        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', 'bar')
            ->willReturn(true);

        $storage
            ->expects(self::once())
            ->method('getItems')
            ->with(['foo'])
            ->willReturn(['foo' => 'bar']);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(3600);
        self::assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        self::assertEquals('bar', $saved['foo']->get());
        self::assertTrue($saved['foo']->isHit());
        // ensure original TTL not modified
        $options = $storage->getOptions();
        self::assertEquals(0, $options->getTtl());
    }

    public function testExpiredItemNotSaved(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::exactly(2))
            ->method('getItem')
            ->with('foo')
            ->willReturnOnConsecutiveCalls(null, 'bar');

        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', 'bar')
            ->willReturn(true);

        assert($this->optionsMock instanceof MockObject);
        $this->optionsMock
            ->expects(self::once())
            ->method('setTtl')
            ->with(0)
            ->willReturnSelf();

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        self::assertTrue($adapter->save($item));
        $saved = $adapter->getItem('foo');
        self::assertFalse($saved->isHit());
    }

    public function testSaveForeignItemThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $item = $this->createMock(CacheItemInterface::class);
        $this->getAdapter($this->createMockedStorage())->save($item);
    }

    public function testSaveItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('setItem')
            ->willThrowException(new Exception\RuntimeException());
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        self::assertFalse($adapter->save($item));
    }

    public function testSaveItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('setItem')
            ->willThrowException(new Exception\InvalidArgumentException());
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->save($item);
    }

    public function testHasItemReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', 'bar')
            ->willReturn(true);

        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(true);

        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        self::assertTrue($adapter->hasItem('foo'));
    }

    public function testHasNonexistentItemReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        self::assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    public function testHasDeferredItemReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertTrue($adapter->hasItem('foo'));
    }

    public function testHasExpiredDeferredItemReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $adapter->saveDeferred($item);
        self::assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testHasItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getAdapter($this->createMockedStorage())->hasItem($key);
    }

    public function testHasItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    public function testHasItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->hasItem('foo');
    }

    public function testClearReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);
        $storage
            ->expects(self::once())
            ->method('clearByNamespace')
            ->with('laminascache')
            ->willReturn(true);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        self::assertTrue($adapter->clear());
    }

    public function testClearWithoutNamespaceReturnsTrue(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => '']);
        $adapter = $this->getAdapter($storage);
        $storage
            ->expects(self::once())
            ->method('flush')
            ->willReturn(true);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        self::assertTrue($adapter->clear());
    }

    public function testClearEmptyReturnsTrue(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => '']);
        $storage
            ->expects(self::once())
            ->method('flush')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearDeferred(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->willReturn(false);

        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->clear();
        self::assertFalse($adapter->hasItem('foo'));
    }

    public function testClearRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => '']);
        $storage
            ->expects(self::once())
            ->method('flush')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceReturnsTrue(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => 'laminascache']);
        $storage
            ->expects(self::once())
            ->method('clearByNamespace')
            ->with('laminascache')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByEmptyNamespaceCallsFlush(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => '']);
        $storage
            ->expects(self::once())
            ->method('flush')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage(null, ['namespace' => 'laminascache']);
        $storage
            ->expects(self::once())
            ->method('clearByNamespace')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->clear());
    }

    public function testDeleteItemReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->with(['foo'])
            ->willReturn(['foo']);

        self::assertTrue($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteDeferredItem(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->deleteItem('foo');
        self::assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testDeleteItemInvalidKeyThrowsException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getAdapter($this->createMockedStorage())->deleteItem($key);
    }

    public function testDeleteItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->deleteItem('foo');
    }

    public function testDeleteItemsReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->with(['foo', 'bar', 'baz'])
            ->willReturn(['foo']);

        self::assertTrue($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteDeferredItems(): void
    {
        $keys = ['foo', 'bar', 'baz'];
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->withConsecutive(['foo'], ['bar'])
            ->willReturn(false);

        $adapter = $this->getAdapter($storage);
        foreach ($keys as $key) {
            $item = $adapter->getItem($key);
            $adapter->saveDeferred($item);
        }
        $keys = ['foo', 'bar'];
        $adapter->deleteItems($keys);
        foreach ($keys as $key) {
            self::assertFalse($adapter->hasItem($key));
        }
        self::assertTrue($adapter->hasItem('baz'));
    }

    public function testDeleteItemsInvalidKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter($this->createMockedStorage())->deleteItems($keys);
    }

    public function testDeleteItemsRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteItemsInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->createMockedStorage();
        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']);
    }

    public function testSaveDeferredReturnsTrue(): void
    {
        $adapter = $this->getAdapter($this->createMockedStorage());
        $item = $adapter->getItem('foo');
        self::assertTrue($adapter->saveDeferred($item));
    }

    public function testSaveDeferredForeignItemThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $item = $this->createMock(CacheItemInterface::class);
        $this->getAdapter($this->createMockedStorage())->saveDeferred($item);
    }

    public function testCommitReturnsTrue(): void
    {
        $storage = $this->createMockedStorage();
        $adapter = $this->getAdapter($storage);
        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', null)
            ->willReturn(true);

        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertTrue($adapter->commit());
    }

    public function testCommitEmptyReturnsTrue(): void
    {
        self::assertTrue($this->getAdapter($this->createMockedStorage())->commit());
    }

    public function testCommitRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage();
        $counter = 0;
        $storage
            ->expects(self::atLeastOnce())
            ->method('setItem')
            ->willReturnCallback(static function () use (&$counter): bool {
                // Using this counter as `__destruct` is calling commit aswell.
                $counter++;
                if ($counter === 1) {
                    throw new Exception\RuntimeException();
                }

                return false;
            });

        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertFalse($adapter->commit());
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
     * @return CacheItemPoolDecorator
     */
    private function getAdapter(StorageInterface $storage): CacheItemPoolDecorator
    {
        return new CacheItemPoolDecorator($storage);
    }

    /**
     * @param array<string,mixed> $capabilities
     *
     * @return Capabilities&MockObject
     */
    private function createCapabilitiesMock(StorageInterface $storage, array $capabilities): Capabilities
    {
        return $this->capabilitiesMock = $this
            ->getMockBuilder(Capabilities::class)
            ->enableProxyingToOriginalMethods()
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $storage,
                new \stdClass(),
                $capabilities,
            ])->getMock();
    }

    private function createOptionsMock(?array $options): AdapterOptions
    {
        $mock = $this->optionsMock = $this
            ->getMockBuilder(AdapterOptions::class)
            ->enableProxyingToOriginalMethods()
            ->enableOriginalConstructor()
            ->getMock();

        if ($options) {
            $mock->setFromArray($options);
        }

        return $mock;
    }
}
