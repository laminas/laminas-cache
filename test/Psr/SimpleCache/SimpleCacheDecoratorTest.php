<?php

namespace LaminasTest\Cache\Psr\SimpleCache;

use ArrayIterator;
use Generator;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheException;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheInvalidArgumentException;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Psr\TestAsset\FlushableNamespaceStorageInterface;
use LaminasTest\Cache\Psr\TestAsset\FlushableStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheException as PsrSimpleCacheException;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrSimpleCacheInvalidArgumentException;
use ReflectionProperty;

use function array_keys;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function str_repeat;

/**
 * Test the PSR-16 decorator.
 *
 * Note to maintainers: the try/catch blocks are done on purpose within this
 * class, instead of expectException*(). This is due to the fact that the
 * decorator is expected to re-throw any caught exceptions as PSR-16 exception
 * types. The class passes the original exception as the previous exception
 * when doing so, and the only way to test that this has happened is to use
 * try/catch blocks and assert identity against the result of getPrevious().
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
class SimpleCacheDecoratorTest extends TestCase
{
    /** @var array<string,bool|string> */
    private $requiredTypes = [
        'NULL'     => true,
        'boolean'  => true,
        'integer'  => true,
        'double'   => true,
        'string'   => true,
        'array'    => true,
        'object'   => 'object',
        'resource' => false,
    ];

    /** @var AdapterOptions&MockObject */
    private $options;

    /** @var StorageInterface&MockObject */
    private $storage;

    /** @var SimpleCacheDecorator */
    private $cache;

    /**
     * @psalm-return Generator<non-empty-string,array{0:Capabilities}>
     */
    public function unsupportedCapabilities(): Generator
    {
        yield 'minimum key length <64 characters' => [
            $this->getMockCapabilities(null, true, 60, 63),
        ];
    }

    protected function setUp(): void
    {
        $this->options = $this->createMock(AdapterOptions::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($this->storage);
        $this->cache = new SimpleCacheDecorator($this->storage);
    }

    private function getMockCapabilities(
        ?array $supportedDataTypes = null,
        bool $staticTtl = true,
        int $minTtl = 60,
        int $maxKeyLength = -1
    ): Capabilities {
        $supportedDataTypes = $supportedDataTypes ?: $this->requiredTypes;
        $capabilities       = $this->createMock(Capabilities::class);
        $capabilities
            ->method('getSupportedDatatypes')
            ->willReturn($supportedDataTypes);

        $capabilities
            ->method('getStaticTtl')
            ->willReturn($staticTtl);
        $capabilities
            ->method('getMinTtl')
            ->willReturn($minTtl);

        $capabilities
            ->method('getMaxKeyLength')
            ->willReturn($maxKeyLength);

        return $capabilities;
    }

    private function mockCapabilities(
        MockObject $storage,
        ?array $supportedDataTypes = null,
        bool $staticTtl = true,
        int $minTtl = 60,
        int $maxKeyLength = -1
    ): void {
        $capabilities = $this->getMockCapabilities(
            $supportedDataTypes,
            $staticTtl,
            $minTtl,
            $maxKeyLength
        );

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);
    }

    public function setSuccessReference(SimpleCacheDecorator $cache, bool $success): void
    {
        $r = new ReflectionProperty($cache, 'success');
        $r->setAccessible(true);
        $r->setValue($cache, $success);
    }

    /**
     * Set of string key names that should be considered invalid for operations
     * that create cache entries.
     *
     * @return array
     */
    public function invalidKeyProvider()
    {
        return [
            'brace-start'   => ['key{', 'cannot contain'],
            'brace-end'     => ['key}', 'cannot contain'],
            'paren-start'   => ['key(', 'cannot contain'],
            'paren-end'     => ['key)', 'cannot contain'],
            'forward-slash' => ['ns/key', 'cannot contain'],
            'back-slash'    => ['ns\key', 'cannot contain'],
            'at'            => ['ns@key', 'cannot contain'],
            'colon'         => ['ns:key', 'cannot contain'],
            'too-long'      => [str_repeat('abcd', 17), 'too long'],
        ];
    }

    /**
     * Set of TTL values that should be considered invalid.
     *
     * @return array
     */
    public function invalidTtls()
    {
        return [
            'false'  => [false],
            'true'   => [true],
            'float'  => [2.75],
            'string' => ['string'],
            'array'  => [[1, 2, 3]],
            'object' => [(object) ['ttl' => 1]],
        ];
    }

    /**
     * TTL values less than 1 should result in immediate cache removal.
     *
     * @return array
     */
    public function invalidatingTtls()
    {
        return [
            'zero'         => [0],
            'negative-1'   => [-1],
            'negative-100' => [-100],
        ];
    }

    public function testStorageNeedsSerializerWillThrowException(): void
    {
        $dataTypes = [
            'staticTtl'          => true,
            'minTtl'             => 1,
            'supportedDatatypes' => [
                'double' => false,
            ],
        ];

        $storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($storage, $dataTypes, false);
        $storage
            ->expects(self::never())
            ->method('getOptions');
        $storage
            ->expects(self::never())
            ->method('setItem');

        $this->expectException(SimpleCacheException::class);
        $this->expectExceptionMessage('serializer plugin');
        new SimpleCacheDecorator($storage);
    }

    public function testItIsASimpleCacheImplementation(): void
    {
        self::assertInstanceOf(SimpleCacheInterface::class, $this->cache);
    }

    public function testGetReturnsDefaultValueWhenUnderlyingStorageDoesNotContainItem(): void
    {
        $testCase = $this;
        $cache    = $this->cache;
        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with('key')
            ->willReturnCallback(static function () use ($testCase, $cache) {
                // Indicating lookup succeeded, but...
                $testCase->setSuccessReference($cache, true);
                // null === not found
                return null;
            });

        self::assertSame('default', $this->cache->get('key', 'default'));
    }

    public function testGetReturnsDefaultValueWhenStorageIndicatesFailure(): void
    {
        $testCase = $this;
        $cache    = $this->cache;
        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with('key')
            ->willReturnCallback(static function () use ($testCase, $cache) {
                // Indicating failure to lookup
                $testCase->setSuccessReference($cache, false);
                return false;
            });

        self::assertSame('default', $this->cache->get('key', 'default'));
    }

    public function testGetReturnsValueReturnedByStorage(): void
    {
        $testCase = $this;
        $cache    = $this->cache;
        $expected = 'returned value';

        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with('key')
            ->willReturnCallback(static function () use ($testCase, $cache, $expected) {
                // Indicating lookup success
                $testCase->setSuccessReference($cache, true);
                return $expected;
            });

        self::assertSame($expected, $this->cache->get('key', 'default'));
    }

    public function testGetShouldReRaiseExceptionThrownByStorage(): void
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with('key')
            ->willThrowException($exception);

        try {
            $this->cache->get('key', 'default');
            self::fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            self::assertSame($exception->getMessage(), $e->getMessage());
            self::assertSame($exception->getCode(), $e->getCode());
            self::assertSame($exception, $e->getPrevious());
        }
    }

    public function testSetProxiesToStorageAndModifiesAndResetsOptions(): void
    {
        $originalTtl = 600;
        $ttl         = 86400;

        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn($originalTtl);

        $this->options
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([$ttl], [$originalTtl])
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $this->storage
            ->expects(self::once())
            ->method('setItem')
            ->with('key', 'value')
            ->willReturn(true);

        self::assertTrue($this->cache->set('key', 'value', $ttl));
    }

    /**
     * @dataProvider invalidTtls
     * @param mixed $ttl
     */
    public function testSetRaisesExceptionWhenTtlValueIsInvalid($ttl)
    {
        $this->storage
            ->expects(self::never())
            ->method('getOptions');
        $this->storage
            ->expects(self::never())
            ->method('setItem');

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->cache->set('key', 'value', $ttl);
    }

    /**
     * @dataProvider invalidatingTtls
     * @param int $ttl
     */
    public function testSetShouldRemoveItemFromCacheIfTtlIsBelow1($ttl)
    {
        $this->storage
            ->expects(self::never())
            ->method('getOptions');
        $this->storage
            ->expects(self::never())
            ->method('setItem');

        $this->storage
            ->expects(self::once())
            ->method('removeItem')
            ->with('key')
            ->willReturn(true);

        self::assertTrue($this->cache->set('key', 'value', $ttl));
    }

    public function testSetShouldReturnFalseWhenProvidedWithPositiveTtlAndStorageDoesNotSupportPerItemTtl(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($storage, null, false);
        $storage
            ->expects(self::never())
            ->method('getOptions');

        $storage
            ->expects(self::never())
            ->method('setItem');

        $cache = new SimpleCacheDecorator($storage);

        self::assertFalse($cache->set('key', 'value', 3600));
    }

    /**
     * @dataProvider invalidatingTtls
     * @param int $ttl
     */
    public function testSetShouldRemoveItemFromCacheIfTtlIsBelow1AndStorageDoesNotSupportPerItemTtl($ttl)
    {
        $storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($storage, null, false);
        $storage
            ->expects(self::never())
            ->method('getOptions');

        $storage
            ->expects(self::never())
            ->method('setItem');

        $storage
            ->expects(self::once())
            ->method('removeItem')
            ->with('key')
            ->willReturn(true);

        $cache = new SimpleCacheDecorator($storage);

        self::assertTrue($cache->set('key', 'value', $ttl));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @param string $key
     * @param string $expectedMessage
     */
    public function testSetShouldRaisePsrInvalidArgumentExceptionForInvalidKeys($key, $expectedMessage)
    {
        $this->storage
            ->expects(self::never())
            ->method('getOptions');

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->cache->set($key, 'value');
    }

    public function testSetShouldAcknowledgeStorageAdapterMaxKeyLengthWithPsrDecorator()
    {
        $validKeyLength   = str_repeat('a', 68);
        $invalidKeyLength = str_repeat('b', 252);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->method('getOptions')
            ->willReturn($this->options);

        $this->mockCapabilities($storage, null, false, 60, 251);
        $storage
            ->expects(self::once())
            ->method('setItem')
            ->with($validKeyLength, 'value')
            ->willReturn(true);

        $cache = new SimpleCacheDecorator($storage);

        self::assertTrue($cache->set($validKeyLength, 'value'));

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->expectExceptionMessage('too long');

        $cache->set($invalidKeyLength, 'value');
    }

    public function testSetShouldReRaiseExceptionThrownByStorage(): void
    {
        $originalTtl = 600;
        $ttl         = 86400;

        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn($originalTtl);

        $this->options
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([$ttl], [$originalTtl])
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage
            ->expects(self::once())
            ->method('setItem')
            ->with('key', 'value')
            ->willThrowException($exception);

        try {
            $this->cache->set('key', 'value', $ttl);
            self::fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            self::assertSame($exception->getMessage(), $e->getMessage());
            self::assertSame($exception->getCode(), $e->getCode());
            self::assertSame($exception, $e->getPrevious());
        }
    }

    public function testDeleteShouldProxyToStorage(): void
    {
        $this->storage
            ->expects(self::once())
            ->method('removeItem')
            ->with('key')
            ->willReturn(true);

        self::assertTrue($this->cache->delete('key'));
    }

    public function testDeleteShouldReturnTrueWhenItemDoesNotExist(): void
    {
        $this->storage
            ->expects(self::once())
            ->method('removeItem')
            ->with('key')
            ->willReturn(false);
        self::assertTrue($this->cache->delete('key'));
    }

    public function testDeleteShouldReturnFalseWhenExceptionThrownByStorage(): void
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage
            ->expects(self::once())
            ->method('removeItem')
            ->with('key')
            ->willThrowException($exception);

        self::assertFalse($this->cache->delete('key'));
    }

    public function testClearReturnsFalseIfStorageIsNotFlushable(): void
    {
        $this->options
            ->expects(self::once())
            ->method('getNamespace')
            ->willReturn(null);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $this->mockCapabilities($storage);

        $cache = new SimpleCacheDecorator($storage);
        self::assertFalse($cache->clear());
    }

    public function testClearProxiesToStorageIfStorageCanBeClearedByNamespace(): void
    {
        $this->options
            ->expects(self::once())
            ->method('getNamespace')
            ->willReturn('foo');

        $storage = $this->createMock(FlushableNamespaceStorageInterface::class);

        $this->mockCapabilities($storage);
        $storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $storage
            ->expects(self::once())
            ->method('clearByNamespace')
            ->with('foo')
            ->willReturn(true);

        $storage
            ->expects(self::never())
            ->method('flush');

        $cache = new SimpleCacheDecorator($storage);
        self::assertTrue($cache->clear());
    }

    public function testClearProxiesToStorageFlushIfStorageCanBeClearedByNamespaceWithNoNamespace(): void
    {
        $this->options
            ->expects(self::once())
            ->method('getNamespace')
            ->willReturn(null);

        $storage = $this->createMock(FlushableNamespaceStorageInterface::class);

        $this->mockCapabilities($storage);
        $storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $storage
            ->expects(self::never())
            ->method('clearByNamespace');

        $storage
            ->expects(self::once())
            ->method('flush')
            ->willReturn(true);

        $cache = new SimpleCacheDecorator($storage);
        self::assertTrue($cache->clear());
    }

    public function testClearProxiesToStorageFlushIfStorageIsFlushable(): void
    {
        $storage = $this->createMock(FlushableStorageInterface::class);
        $this->mockCapabilities($storage);
        $storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $storage
            ->expects(self::once())
            ->method('flush')
            ->willReturn(true);

        $cache = new SimpleCacheDecorator($storage);
        self::assertTrue($cache->clear());
    }

    public function testGetMultipleProxiesToStorageAndProvidesDefaultsForUnfoundKeysWhenNonNullDefaultPresent(): void
    {
        $keys     = ['one', 'two', 'three'];
        $expected = [
            'one'   => 1,
            'two'   => 'default',
            'three' => 3,
        ];

        $this->storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([
                'one'   => 1,
                'three' => 3,
            ]);

        self::assertEquals($expected, $this->cache->getMultiple($keys, 'default'));
    }

    public function testGetMultipleProxiesToStorageAndOmitsValuesForUnfoundKeysWhenNullDefaultPresent(): void
    {
        $keys     = ['one', 'two', 'three'];
        $expected = [
            'one'   => 1,
            'two'   => null,
            'three' => 3,
        ];

        $this->storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willReturn([
                'one'   => 1,
                'three' => 3,
            ]);

        self::assertEquals($expected, $this->cache->getMultiple($keys));
    }

    public function testGetMultipleReturnsValuesFromStorageWhenProvidedWithIterableKeys(): void
    {
        $keys     = new ArrayIterator(['one', 'two', 'three']);
        $expected = [
            'one'   => 1,
            'two'   => 'two',
            'three' => 3,
        ];

        $this->storage
            ->expects(self::once())
            ->method('getItems')
            ->with(iterator_to_array($keys))
            ->willReturn($expected);

        self::assertEquals($expected, $this->cache->getMultiple($keys));
    }

    public function testGetMultipleReRaisesExceptionFromStorage(): void
    {
        $keys      = ['one', 'two', 'three'];
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);

        $this->storage
            ->expects(self::once())
            ->method('getItems')
            ->with($keys)
            ->willThrowException($exception);

        try {
            $this->cache->getMultiple($keys);
            self::fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            self::assertSame($exception->getMessage(), $e->getMessage());
            self::assertSame($exception->getCode(), $e->getCode());
            self::assertSame($exception, $e->getPrevious());
        }
    }

    public function testSetMultipleProxiesToStorageAndModifiesAndResetsOptions(): void
    {
        $originalTtl = 600;
        $ttl         = 86400;

        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn($originalTtl);

        $this->options
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([$ttl], [$originalTtl])
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $values = ['one' => 1, 'three' => 3];

        $this->storage
            ->expects(self::once())
            ->method('setItems')
            ->with($values)
            ->willReturn([]);

        self::assertTrue($this->cache->setMultiple($values, $ttl));
    }

    public function testSetMultipleProxiesToStorageAndModifiesAndResetsOptionsWhenProvidedAnIterable(): void
    {
        $originalTtl = 600;
        $ttl         = 86400;

        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn($originalTtl);

        $this->options
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([$ttl], [$originalTtl])
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $values = new ArrayIterator([
            'one'   => 1,
            'three' => 3,
        ]);

        $this->storage
            ->expects(self::once())
            ->method('setItems')
            ->with(iterator_to_array($values))
            ->willReturn([]);

        self::assertTrue($this->cache->setMultiple($values, $ttl));
    }

    /**
     * @dataProvider invalidTtls
     * @param mixed $ttl
     */
    public function testSetMultipleRaisesExceptionWhenTtlValueIsInvalid($ttl)
    {
        $values = ['one' => 1, 'three' => 3];
        $this->storage
            ->expects(self::never())
            ->method('getOptions');

        $this->storage
            ->expects(self::never())
            ->method('setItems');

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->cache->setMultiple($values, $ttl);
    }

    /**
     * @dataProvider invalidatingTtls
     * @param int $ttl
     */
    public function testSetMultipleShouldRemoveItemsFromCacheIfTtlIsBelow1($ttl)
    {
        $values = [
            'one'   => 1,
            'two'   => 'true',
            'three' => ['tags' => true],
        ];

        $this->storage
            ->expects(self::never())
            ->method('getOptions');
        $this->storage
            ->expects(self::never())
            ->method('setItems');

        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with(array_keys($values))->willReturn([]);

        self::assertTrue($this->cache->setMultiple($values, $ttl));
    }

    public function testSetMultipleShouldReturnFalseWhenProvidedWithPositiveTtlAndStorageDoesNotSupportPerItemTtl(): void
    {
        $values = [
            'one'   => 1,
            'two'   => 'true',
            'three' => ['tags' => true],
        ];

        $storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($storage, null, false);
        $storage
            ->expects(self::never())
            ->method('getOptions');

        $storage
            ->expects(self::never())
            ->method('setItems');

        $cache = new SimpleCacheDecorator($storage);

        self::assertFalse($cache->setMultiple($values, 60));
    }

    /**
     * @dataProvider invalidatingTtls
     * @param int $ttl
     */
    public function testSetMultipleShouldRemoveItemsFromCacheIfTtlIsBelow1AndStorageDoesNotSupportPerItemTtl($ttl)
    {
        $values = [
            'one'   => 1,
            'two'   => 'true',
            'three' => ['tags' => true],
        ];

        $storage = $this->createMock(StorageInterface::class);
        $this->mockCapabilities($storage, null, false);
        $storage
            ->expects(self::never())
            ->method('getOptions');

        $storage
            ->expects(self::never())
            ->method('setItems');

        $storage
            ->expects(self::once())
            ->method('removeItems')
            ->with(array_keys($values))
            ->willReturn([]);

        $cache = new SimpleCacheDecorator($storage);

        self::assertTrue($cache->setMultiple($values, $ttl));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @param string $key
     * @param string $expectedMessage
     */
    public function testSetMultipleShouldRaisePsrInvalidArgumentExceptionForInvalidKeys($key, $expectedMessage)
    {
        $this->storage
            ->expects(self::never())
            ->method('getOptions');

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->cache->setMultiple([$key => 'value']);
    }

    public function testSetMultipleReRaisesExceptionFromStorage(): void
    {
        $originalTtl = 600;
        $ttl         = 86400;

        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn($originalTtl);

        $this->options
            ->expects(self::exactly(2))
            ->method('setTtl')
            ->withConsecutive([$ttl], [$originalTtl])
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $values    = ['one' => 1, 'three' => 3];

        $this->storage
            ->expects(self::once())
            ->method('setItems')
            ->with($values)
            ->willThrowException($exception);

        try {
            $this->cache->setMultiple($values, $ttl);
            self::fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            self::assertSame($exception->getMessage(), $e->getMessage());
            self::assertSame($exception->getCode(), $e->getCode());
            self::assertSame($exception, $e->getPrevious());
        }
    }

    public function testDeleteMultipleProxiesToStorageAndReturnsTrueWhenStorageReturnsEmptyArray(): void
    {
        $keys = ['one', 'two', 'three'];
        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with($keys)
            ->willReturn([]);
        self::assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleReturnsTrueWhenProvidedWithIterableAndStorageReturnsEmptyArray(): void
    {
        $keys = new ArrayIterator(['one', 'two', 'three']);
        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with(iterator_to_array($keys))
            ->willReturn([]);

        self::assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleReturnsTrueWhenProvidedWithAnEmptyArrayOfKeys(): void
    {
        $this->storage
            ->expects(self::never())
            ->method('removeItems');

        self::assertTrue($this->cache->deleteMultiple([]));
    }

    public function testDeleteMultipleProxiesToStorageAndReturnsFalseIfStorageReturnsNonEmptyArray(): void
    {
        $keys = ['one', 'two', 'three'];
        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with($keys)
            ->willReturn(['two']);

        $this->storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('two')
            ->willReturn(true);

        self::assertFalse($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleReturnsTrueIfKeyReturnedByStorageDoesNotExist(): void
    {
        $keys = ['one', 'two', 'three'];
        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with($keys)
            ->willReturn(['two']);

        $this->storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('two')
            ->willReturn(false);

        self::assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleReturnFalseWhenExceptionThrownByStorage(): void
    {
        $keys      = ['one', 'two', 'three'];
        $exception = new Exception\InvalidArgumentException('bad key', 500);
        $this->storage
            ->expects(self::once())
            ->method('removeItems')
            ->with($keys)
            ->willThrowException($exception);

        self::assertFalse($this->cache->deleteMultiple($keys));
    }

    /**
     * @return array<string,array{0:bool}>
     */
    public function hasResultProvider(): array
    {
        return [
            'true'  => [true],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider hasResultProvider
     */
    public function testHasProxiesToStorage(bool $result)
    {
        $this->storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('key')
            ->willReturn($result);

        self::assertSame($result, $this->cache->has('key'));
    }

    public function testHasReRaisesExceptionThrownByStorage(): void
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage
            ->expects(self::once())
            ->method('hasItem')
            ->with('key')
            ->willThrowException($exception);

        try {
            $this->cache->has('key');
            self::fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            self::assertSame($exception->getMessage(), $e->getMessage());
            self::assertSame($exception->getCode(), $e->getCode());
            self::assertSame($exception, $e->getPrevious());
        }
    }

    public function testUseTtlFromOptionsWhenNotProvidedOnSet(): void
    {
        $capabilities = $this->getMockCapabilities();

        $storage = new TestAsset\TtlStorage(['ttl' => 20]);
        $storage->setCapabilities($capabilities);
        $cache = new SimpleCacheDecorator($storage);

        $cache->set('foo', 'bar');
        self::assertSame(20, $storage->ttl['foo']);
        self::assertSame(20, $storage->getOptions()->getTtl());
    }

    public function testUseTtlFromOptionsWhenNotProvidedOnSetMultiple(): void
    {
        $capabilities = $this->getMockCapabilities();

        $storage = new TestAsset\TtlStorage(['ttl' => 20]);
        $storage->setCapabilities($capabilities);
        $cache = new SimpleCacheDecorator($storage);

        $cache->setMultiple(['foo' => 'bar', 'bar' => 'baz']);
        self::assertSame(20, $storage->ttl['foo']);
        self::assertSame(20, $storage->ttl['bar']);
        self::assertSame(20, $storage->getOptions()->getTtl());
    }

    public function testUseTtlFromOptionsOnSetMocking(): void
    {
        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn(40);

        $this->options
            ->expects(self::once())
            ->method('setTtl')
            ->with(40)
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);
        $this->storage
            ->expects(self::once())
            ->method('setItem')
            ->with('foo', 'bar')
            ->willReturn(true);

        self::assertTrue($this->cache->set('foo', 'bar'));
    }

    public function testUseTtlFromOptionsOnSetMultipleMocking(): void
    {
        $this->options
            ->expects(self::once())
            ->method('getTtl')
            ->willReturn(40);
        $this->options
            ->expects(self::once())
            ->method('setTtl')
            ->with(40)
            ->willReturnSelf();

        $this->storage
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn($this->options);

        $this->storage
            ->expects(self::once())
            ->method('setItems')
            ->with(['foo' => 'bar', 'boo' => 'baz'])
            ->willReturn([]);

        self::assertTrue($this->cache->setMultiple(['foo' => 'bar', 'boo' => 'baz']));
    }

    /**
     * @dataProvider unsupportedCapabilities
     */
    public function testWillThrowExceptionWhenStorageDoesNotFulfillMinimumRequirements(Capabilities $capabilities): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->expectExceptionMessage('does not fulfill the minimum requirements');

        new SimpleCacheDecorator($storage);
    }

    public function testWillUsePcreMaximumQuantifierLengthIfAdapterAllowsMoreThanThat(): void
    {
        $storage      = $this->createMock(StorageInterface::class);
        $capabilities = $this->getMockCapabilities(
            null,
            true,
            60,
            SimpleCacheDecorator::$pcreMaximumQuantifierLength
        );

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $decorator = new SimpleCacheDecorator($storage);
        $key       = str_repeat('a', SimpleCacheDecorator::$pcreMaximumQuantifierLength);
        $this->expectException(SimpleCacheInvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'key is too long. Must be no more than %d characters',
            SimpleCacheDecorator::$pcreMaximumQuantifierLength - 1
        ));
        $decorator->has($key);
    }

    public function testPcreMaximumQuantifierLengthWontResultInCompilationError(): void
    {
        self::assertEquals(
            0,
            preg_match(
                sprintf(
                    '/^.{%d,}$/',
                    SimpleCacheDecorator::$pcreMaximumQuantifierLength
                ),
                ''
            )
        );
    }

    public function testGeneratorFloatKeyIsDetectedAsInvalidKey(): void
    {
        $storage = $this->createMock(StorageInterface::class);

        $capabilities = $this->getMockCapabilities();

        $storage
            ->method('getCapabilities')
            ->willReturn($capabilities);

        $decorator = new SimpleCacheDecorator($storage);
        $storage
            ->expects(self::never())
            ->method('setItems');

        $iterable = (static function (): iterable {
            yield 2.5 => 'value';
        })();

        $this->expectException(SimpleCacheInvalidArgumentException::class);
        self::assertTrue($decorator->setMultiple($iterable));
    }

    public function testWillThrowExceptionWhenUsingFilesystemAdapterAndUseInvalidCacheKey(): void
    {
        $storage = $this->storage;
        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->willThrowException(new Exception\InvalidArgumentException());

        $decorator = new SimpleCacheDecorator($storage);

        /** @psalm-suppress InvalidArgument PSR-16 exception interfaces does not extend Throwable in v1 */
        $this->expectException(PsrSimpleCacheInvalidArgumentException::class);
        $decorator->get('127.0.0.1');
    }

    public function testWillThrowExceptionWhenUsingFilesystemAdapterAndAGenericErrorOccurs(): void
    {
        $storage = $this->storage;
        $storage
            ->expects(self::once())
            ->method('getItem')
            ->willThrowException(new SimpleCacheException());

        $decorator = new SimpleCacheDecorator($storage);

        /** @psalm-suppress InvalidArgument PSR-16 exception interfaces does not extend Throwable in v1 */
        $this->expectException(PsrSimpleCacheException::class);
        $decorator->get('127-0-0-1');
    }
}
