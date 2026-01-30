<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\CacheItemPool;

use DateTimeImmutable;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheException;
use Laminas\Cache\Psr\CacheItemPool\CacheItem;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Psr\CacheItemPool\TestAsset\FlushableStorageAdapterInterface;
use LaminasTest\Cache\Psr\TestAsset\FlushableNamespaceStorageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use Throwable;

use function array_keys;
use function array_map;
use function assert;
use function preg_match;
use function sprintf;
use function str_repeat;
use function time;

/**
 * @psalm-import-type SupportedDataTypesArrayShape from Capabilities
 */
final class CacheItemPoolDecoratorTest extends TestCase
{
    /** @var StorageInterface&FlushableInterface&MockObject */
    private StorageInterface&FlushableInterface&MockObject $storage;

    private ?CacheItemPoolDecorator $adapter;

    private const REQUIRED_TYPES = [
        'NULL'     => true,
        'boolean'  => true,
        'integer'  => true,
        'double'   => true,
        'string'   => true,
        'array'    => true,
        'object'   => 'object',
        'resource' => false,
    ];

    private AdapterOptions&MockObject $options;

    protected function setUp(): void
    {
        parent::setUp();
        $this->options = $this->createMock(AdapterOptions::class);
        $this->storage = $this->createMockedStorage();
        $this->adapter = $this->getAdapter($this->storage);
    }

    /**
     * @param SupportedDataTypesArrayShape|null $supportedDataTypes
     * @param int<-1,max> $maxKeyLength
     * @return StorageInterface&FlushableInterface&ClearByNamespaceInterface&MockObject
     */
    private function createMockedStorage(
        ?AdapterOptions $options = null,
        ?array $supportedDataTypes = null,
        bool $ttlSupported = true,
        int $maxKeyLength = -1,
        bool $useRequestTime = false,
    ): StorageInterface {
        $storage = $this->createMock(FlushableNamespaceStorageInterface::class);

        $capabilities = $this->createCapabilities(
            $supportedDataTypes,
            $ttlSupported,
            $maxKeyLength,
            $useRequestTime,
        );

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $storage
            ->method('getOptions')
            ->willReturn($options ?? $this->options);

        return $storage;
    }

    public function testStorageNeedsSerializerWillThrowException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMock(FlushableStorageAdapterInterface::class);

        $capabilities = $this->createCapabilities(supportedDataTypes: [
            'NULL'     => true,
            'boolean'  => true,
            'integer'  => true,
            'double'   => false,
            'string'   => true,
            'array'    => true,
            'object'   => 'object',
            'resource' => false,
        ]);

        $storage
            ->expects($this->once())
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $this->getAdapter($storage);
    }

    public function testBackendDoesNotSupportTtlThrowsException(): void
    {
        $this->expectException(CacheException::class);
        $storage = $this->createMockedStorage(null, null, ttlSupported: false);
        $this->getAdapter($storage);
    }

    public function testGetDeferredItem(): void
    {
        $storage = $this->storage;

        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturnOnConsecutiveCalls(null);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $item    = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->saveDeferred($item);

        $item = $adapter->getItem('foo');
        self::assertTrue($item->isHit());
        self::assertEquals('bar', $item->get());
    }

    #[DataProvider('invalidKeyProvider')]
    public function testGetItemInvalidKeyThrowsException(mixed $key)
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $this->getAdapter($storage)->getItem($key);
    }

    public function testGetItemRuntimeExceptionIsMiss(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new Exception\RuntimeException());

        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        self::assertFalse($item->isHit());
    }

    public function testGetItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->getItem('foo');
    }

    public function testGetNonexistentItems(): void
    {
        $keys    = ['foo', 'bar'];
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([]);

        $adapter = $this->getAdapter($storage);
        $items   = $adapter->getItems($keys);
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
        $keys    = ['foo', 'bar'];
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([]);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $items   = $adapter->getItems($keys);
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
        $keys    = ['foo', 'bar'];
        $storage = $this->storage;

        $storage
            ->expects($this->once())
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
        $keys = ['ok'] + self::getInvalidKeys();

        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->getItems($keys);
    }

    public function testGetItemsRuntimeExceptionIsMiss(): void
    {
        $keys    = ['foo', 'bar'];
        $storage = $this->storage;
        $storage
            ->expects($this->once())
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
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->getItems(['foo', 'bar']);
    }

    public function testSaveItem(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->wilLReturn(null);

        $storage
            ->expects($this->once())
            ->method('getItems')
            ->with(['foo'])
            ->willReturn(['foo' => 'bar']);

        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        $item->set('bar');
        self::assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        self::assertEquals('bar', $saved['foo']->get());
        self::assertTrue($saved['foo']->isHit());
    }

    public function testSaveItemWithExpiration(): void
    {
        $storage = $this->storage;
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $invokedCount = self::exactly(2);
        $this->options
            ->expects($invokedCount)
            ->method('setTtl')
            ->with(self::callback(static function ($arg) use ($invokedCount): bool {
                switch ($invokedCount->numberOfInvocations()) {
                    case 1:
                        self::assertEquals(3600, $arg);
                        return true;
                    case 2:
                        self::assertEquals(0, $arg);
                        return true;
                    default:
                        return false;
                }
            }))
            ->willReturnSelf();

        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $storage
            ->expects($this->once())
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
        $storage = $this->storage;
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects(self::exactly(2))
            ->method('getItem')
            ->with('foo')
            ->willReturnOnConsecutiveCalls(null, 'bar');

        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $this->options
            ->expects(self::exactly(2))
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

        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->save($item);
    }

    public function testSaveItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('setItems')
            ->willThrowException(new Exception\RuntimeException());
        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        self::assertFalse($adapter->save($item));
    }

    public function testSaveItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('setItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        $adapter->save($item);
    }

    public function testHasItemReturnsTrue(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(true);

        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        self::assertTrue($adapter->hasItem('foo'));
    }

    public function testHasNonexistentItemReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        self::assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    public function testHasDeferredItemReturnsTrue(): void
    {
        $storage = $this->storage;

        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $item    = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertTrue($adapter->hasItem('foo'));
    }

    public function testHasExpiredDeferredItemReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn(null);

        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $item    = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $adapter->saveDeferred($item);
        self::assertFalse($adapter->hasItem('foo'));
    }

    #[DataProvider('invalidKeyProvider')]
    public function testHasItemInvalidKeyThrowsException(mixed $key)
    {
        $this->expectException(InvalidArgumentException::class);
        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->hasItem($key);
    }

    public function testHasItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    public function testHasItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->hasItem('foo');
    }

    public function testClearReturnsTrue(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $this->options
            ->expects($this->once())
            ->method('getNamespace')
            ->willReturn('laminascache');

        $adapter = $this->getAdapter($storage);
        $storage
            ->expects($this->once())
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
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => '']));
        $adapter = $this->getAdapter($storage);
        $storage
            ->expects($this->once())
            ->method('flush')
            ->willReturn(true);

        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => 'bar'])
            ->willReturn(['foo' => true]);

        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        self::assertTrue($adapter->clear());
    }

    public function testClearEmptyReturnsTrue(): void
    {
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => '']));
        $storage
            ->expects($this->once())
            ->method('flush')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearDeferred(): void
    {
        $storage = $this->storage;
        $adapter = $this->getAdapter($storage);

        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->willReturn(false);

        $this->options
            ->expects($this->once())
            ->method('getNamespace')
            ->willReturn('bar');

        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->storage
            ->expects($this->once())
            ->method('clearByNamespace')
            ->with('bar')
            ->willReturn(true);

        $adapter->clear();
        self::assertFalse($adapter->hasItem('foo'));
    }

    public function testClearRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => '']));
        $storage
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceReturnsTrue(): void
    {
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => 'laminascache']));
        $storage
            ->expects($this->once())
            ->method('clearByNamespace')
            ->with('laminascache')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByEmptyNamespaceCallsFlush(): void
    {
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => '']));
        $storage
            ->expects($this->once())
            ->method('flush')
            ->willReturn(true);

        self::assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->createMockedStorage(new AdapterOptions(['namespace' => 'laminascache']));
        $storage
            ->expects($this->once())
            ->method('clearByNamespace')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->clear());
    }

    public function testDeleteItemReturnsTrue(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->with(['foo'])
            ->willReturn([]);

        self::assertTrue($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteDeferredItem(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        $adapter = $this->getAdapter($storage);
        $item    = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->deleteItem('foo');
        self::assertFalse($adapter->hasItem('foo'));
    }

    #[DataProvider('invalidKeyProvider')]
    public function testDeleteItemInvalidKeyThrowsException(mixed $key)
    {
        $this->expectException(InvalidArgumentException::class);
        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->deleteItem($key);
    }

    public function testDeleteItemRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->deleteItem('foo'));
    }

    public function testDeleteItemInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->deleteItem('foo');
    }

    public function testDeleteItemsReturnsTrue(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->with(['foo', 'bar', 'baz'])
            ->willReturn([]);

        self::assertTrue($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteDeferredItems(): void
    {
        $keys    = ['foo', 'bar', 'baz'];
        $storage = $this->storage;

        $invokedCount = self::exactly(2);
        $storage
            ->expects($invokedCount)
            ->method('hasItem')
            ->with(self::callback(static function (string $arg) use ($invokedCount): bool {
                switch ($invokedCount->numberOfInvocations()) {
                    case 1:
                        self::assertSame('foo', $arg);
                        return true;
                    case 2:
                        self::assertSame('bar', $arg);
                        return true;
                    default:
                        return false;
                }
            }))
            ->willReturn(false);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
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
        $keys = ['ok'] + self::getInvalidKeys();
        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->deleteItems($keys);
    }

    public function testDeleteItemsRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->willThrowException(new Exception\RuntimeException());
        self::assertFalse($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteItemsInvalidArgumentExceptionRethrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('removeItems')
            ->willThrowException(new Exception\InvalidArgumentException());
        $this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']);
    }

    public function testSaveDeferredReturnsTrue(): void
    {
        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $item    = $adapter->getItem('foo');
        self::assertTrue($adapter->saveDeferred($item));
    }

    public function testSaveDeferredForeignItemThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $item = $this->createMock(CacheItemInterface::class);
        assert($this->adapter instanceof CacheItemPoolDecorator);
        $this->adapter->saveDeferred($item);
    }

    public function testCommitReturnsTrue(): void
    {
        $storage = $this->storage;
        $adapter = $this->getAdapter($storage);
        $storage
            ->expects($this->once())
            ->method('setItems')
            ->with(['foo' => null])
            ->willReturn(['foo' => true]);

        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertTrue($adapter->commit());
    }

    public function testCommitEmptyReturnsTrue(): void
    {
        assert($this->adapter instanceof CacheItemPoolDecorator);
        self::assertTrue($this->adapter->commit());
    }

    public function testCommitRuntimeExceptionReturnsFalse(): void
    {
        $storage = $this->storage;
        $storage
            ->expects($this->once())
            ->method('setItems')
            ->willThrowException(new Exception\RuntimeException());

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $adapter = $this->adapter;
        $item    = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        self::assertFalse($adapter->commit());
    }

    /**
     * @return array<int,array{0:string|object}>
     * @psalm-return list<array{0:string|object}>
     */
    public static function invalidKeyProvider(): array
    {
        return array_map(static fn($v): array => [$v], self::getInvalidKeys());
    }

    /**
     * @return list<string|object>
     */
    private static function getInvalidKeys(): array
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
        ];
    }

    private function getAdapter(StorageInterface&FlushableInterface $storage): CacheItemPoolDecorator
    {
        return new CacheItemPoolDecorator($storage);
    }

    protected function tearDown(): void
    {
        try {
            assert($this->adapter instanceof CacheItemPoolDecorator);
            $this->adapter->clear();
        } catch (Throwable) {
            /** Cleanup deferred items as {@see CacheItemPoolDecorator::__destruct} is gonna try to store them. */
        } finally {
            $this->adapter = null;
        }
        parent::tearDown();
    }

    #[DataProvider('deletionVerificationProvider')]
    public function testWillVerifyKeyExistenceByUsingHasItemsWhenDeletionWasNotSuccessful(
        bool $exists,
        bool $successful,
    ): void {
        $this->storage
            ->expects($this->once())
            ->method('removeItems')
            ->with(['foo'])
            ->willReturn(['foo']);

        $this->storage
            ->expects($this->once())
            ->method('hasItems')
            ->with(['foo'])
            ->willReturn(['foo' => $exists]);

        assert($this->adapter instanceof CacheItemPoolDecorator);
        self::assertEquals($successful, $this->adapter->deleteItems(['foo']));
    }

    public function testWontSaveAlreadyExpiredCacheItemAsDeferredItem(): void
    {
        $adapter = $this->createMock(FlushableStorageAdapterInterface::class);
        $adapter
            ->expects(self::atLeast(3))
            ->method('getCapabilities')
            ->willReturn($this->createCapabilities());

        $adapter
            ->expects(self::never())
            ->method('removeItems');
        $adapter
            ->expects(self::never())
            ->method('setItem');

        $adapter
            ->expects($this->once())
            ->method('hasItem')
            ->with('foo')
            ->willReturn(false);

        $expiryTime = DateTimeImmutable::createFromFormat('U', (string) (time() - 1));
        self::assertNotFalse($expiryTime);

        $item = new CacheItem('foo', 'bar', false);
        $item->expiresAt($expiryTime);

        $cache = new CacheItemPoolDecorator($adapter);
        $cache->saveDeferred($item);

        self::assertFalse($item->isHit());
        self::assertFalse($cache->hasItem($item->getKey()));
    }

    /**
     * @return array<non-empty-string,array{0:bool,1:bool}>
     */
    public static function deletionVerificationProvider(): array
    {
        return [
            'deletion failed due to hasItems states the key still exists'       => [true, false],
            'deletion successful due to hasItems states the key does not exist' => [false, true],
        ];
    }

    public function testWillUsePcreMaximumQuantifierLengthIfAdapterAllowsMoreThanThat(): void
    {
        $storage      = $this->createMock(FlushableStorageAdapterInterface::class);
        $capabilities = $this->createCapabilities(
            maxKeyLength: SimpleCacheDecorator::$pcreMaximumQuantifierLength,
        );

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $decorator = new CacheItemPoolDecorator($storage);
        $key       = str_repeat('a', CacheItemPoolDecorator::$pcreMaximumQuantifierLength);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'key is too long. Must be no more than %d characters',
            CacheItemPoolDecorator::$pcreMaximumQuantifierLength - 1,
        ));
        $decorator->getItem($key);
    }

    public function testPcreMaximumQuantifierLengthWontResultInCompilationError(): void
    {
        self::assertEquals(
            0,
            preg_match(
                sprintf(
                    '/^.{%d,}$/',
                    CacheItemPoolDecorator::$pcreMaximumQuantifierLength,
                ),
                '',
            ),
        );
    }

    /**
     * @param SupportedDataTypesArrayShape|null $supportedDataTypes
     * @param int<-1,max> $maxKeyLength
     */
    private function createCapabilities(
        ?array $supportedDataTypes = null,
        bool $ttlSupported = true,
        int $maxKeyLength = -1,
        bool $useRequestTime = false,
    ): Capabilities {
        return new Capabilities(
            maxKeyLength: $maxKeyLength,
            ttlSupported: $ttlSupported,
            supportedDataTypes: $supportedDataTypes ?? self::REQUIRED_TYPES,
            usesRequestTime: $useRequestTime,
        );
    }

    public function testKeepsDeferredItemsWhenCommitFails(): void
    {
        $failedItem1   = new CacheItem('keyOfFailedItem1', 'foo', false);
        $failedItem2   = new CacheItem('keyOfFailedItem2', 'foo', false);
        $succeededItem = new CacheItem('keyOfSucceededItem', 'foo', false);

        assert($this->adapter instanceof CacheItemPoolDecorator);

        $this->adapter->saveDeferred($failedItem1);
        $this->adapter->saveDeferred($failedItem2);
        $this->adapter->saveDeferred($succeededItem);

        $this->storage
            ->expects($this->once())
            ->method('setItems')
            ->willReturn(['keyOfFailedItem1', 'keyOfFailedItem2']);

        self::assertFalse($this->adapter->commit());
        self::assertTrue($this->adapter->hasItem('keyOfFailedItem1'));
        self::assertTrue($this->adapter->hasItem('keyOfFailedItem2'));
    }

    public function testPassesClockToCacheItem(): void
    {
        $clock   = $this->createMock(ClockInterface::class);
        $adapter = new CacheItemPoolDecorator($this->storage, $clock);
        $item    = $adapter->getItem('notExistingItem');
        self::assertInstanceOf(CacheItem::class, $item);
        self::assertFalse($item->isHit());

        $now = new DateTimeImmutable('now');
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn($now);

        $item->expiresAt($now);
        self::assertEquals(0, $item->getTtl());
    }
}
