<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-import-type StorageAdapterArrayConfigurationMapType from StorageCacheAbstractServiceFactory
 */
final class StorageCacheAbstractServiceFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    protected $container;

    /** @var StorageAdapterFactoryInterface&MockObject */
    private $storageAdapterFactory;

    /** @var StorageCacheAbstractServiceFactory */
    private $abstractFactory;

    /** @psalm-var array{caches:StorageAdapterArrayConfigurationMapType} */
    private $config;

    public function setUp(): void
    {
        $this->storageAdapterFactory = $this->createMock(StorageAdapterFactoryInterface::class);
        $this->config                = [
            'caches' => [
                'Memory' => [
                    'name'    => 'Memory',
                    'plugins' => [
                        ['name' => 'Serializer'],
                        ['name' => 'ClearExpiredByFactor'],
                    ],
                ],
                'Foo'    => [
                    'name'    => 'Memory',
                    'plugins' => [
                        ['name' => 'ClearExpiredByFactor'],
                        ['name' => 'Serializer'],
                    ],
                ],
            ],
        ];

        $this->container       = $this->createMock(ContainerInterface::class);
        $this->abstractFactory = new StorageCacheAbstractServiceFactory();
    }

    public function testCanRetrieveCacheByName(): void
    {
        $this->storageAdapterFactory
            ->expects(self::exactly(2))
            ->method('assertValidConfigurationStructure')
            ->withConsecutive(
                [
                    [
                        'name'    => 'Memory',
                        'plugins' => [
                            ['name' => 'Serializer'],
                            ['name' => 'ClearExpiredByFactor'],
                        ],
                    ],
                ],
                [
                    [
                        'name'    => 'Memory',
                        'plugins' => [
                            ['name' => 'ClearExpiredByFactor'],
                            ['name' => 'Serializer'],
                        ],
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $storage = $this->createMock(StorageInterface::class);
        $this->storageAdapterFactory
            ->expects(self::exactly(2))
            ->method('createFromArrayConfiguration')
            ->withConsecutive(
                [
                    [
                        'name'    => 'Memory',
                        'plugins' => [
                            ['name' => 'Serializer'],
                            ['name' => 'ClearExpiredByFactor'],
                        ],
                    ],
                ],
                [
                    [
                        'name'    => 'Memory',
                        'plugins' => [
                            ['name' => 'ClearExpiredByFactor'],
                            ['name' => 'Serializer'],
                        ],
                    ],
                ]
            )->willReturn($storage);

        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([StorageAdapterFactoryInterface::class], ['config'])
            ->willReturn(true);

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([StorageAdapterFactoryInterface::class], ['config'])
            ->willReturnOnConsecutiveCalls($this->storageAdapterFactory, $this->config);

        $cacheA = ($this->abstractFactory)($this->container, 'Memory');
        self::assertSame($storage, $cacheA);

        $cacheB = ($this->abstractFactory)($this->container, 'Foo');
        self::assertSame($storage, $cacheB);
    }

    public function testInvalidCacheServiceNameWillBeIgnored(): void
    {
        self::assertFalse(
            $this->abstractFactory->canCreate($this->container, 'invalid')
        );
    }

    public function testCannotCreateAnyCacheWhenStorageFactoryIsNotAvailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->with(StorageAdapterFactoryInterface::class)
            ->willReturn(false);

        $this->container
            ->expects(self::never())
            ->method('get')
            ->withAnyParameters();

        self::assertFalse(
            $this->abstractFactory->canCreate($container, 'foo')
        );
    }
}
