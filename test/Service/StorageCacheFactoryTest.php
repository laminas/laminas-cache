<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StorageCacheFactory;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StorageCacheFactoryTest extends TestCase
{
    /** @var StorageCacheFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new StorageCacheFactory();
    }

    public function testWillUseStorageAdapterFactoryToCreateAdapter(): void
    {
        $cacheConfig = [
            'foo' => 'bar',
        ];

        $adapterFactory = $this->createMock(StorageAdapterFactoryInterface::class);
        $adapterFactory
            ->expects(self::once())
            ->method('assertValidConfigurationStructure')
            ->with($cacheConfig);

        $adapter = $this->createMock(StorageInterface::class);

        $adapterFactory
            ->expects(self::once())
            ->method('createFromArrayConfiguration')
            ->with($cacheConfig)
            ->willReturn($adapter);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->withConsecutive([StorageAdapterFactoryInterface::class], ['config'])
            ->willReturnOnConsecutiveCalls(
                $adapterFactory,
                ['cache' => $cacheConfig]
            );

        self::assertSame($adapter, ($this->factory)($container));
    }
}
