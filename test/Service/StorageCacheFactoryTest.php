<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StorageCacheFactory;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StorageCacheFactoryTest extends TestCase
{
    private StorageCacheFactory $factory;

    /** @var MockObject&ContainerInterface */
    private $container;

    /** @var array<string,mixed> */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory   = new StorageCacheFactory();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->config    = [
            'cache' => [
                'adapter' => 'Memory',
                'plugins' => ['Serializer', 'ClearExpiredByFactor'],
            ],
        ];
    }

    public function testWillUseStorageAdapterFactoryInterface(): void
    {
        $factory = $this->createMock(StorageAdapterFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('createFromArrayConfiguration')
            ->with($this->config['cache'])
            ->willReturn($this->createMock(StorageInterface::class));

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['config'], [StorageAdapterFactoryInterface::class])
            ->willReturnOnConsecutiveCalls($this->config, $factory);

        ($this->factory)($this->container);
    }

    public function testWillAssertConfigurationValidity(): void
    {
        $factory = $this->createMock(StorageAdapterFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('assertValidConfigurationStructure')
            ->with($this->config['cache']);

        $factory
            ->expects(self::once())
            ->method('createFromArrayConfiguration')
            ->with($this->config['cache'])
            ->willReturn($this->createMock(StorageInterface::class));

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['config'], [StorageAdapterFactoryInterface::class])
            ->willReturnOnConsecutiveCalls($this->config, $factory);

        ($this->factory)($this->container);
    }
}
