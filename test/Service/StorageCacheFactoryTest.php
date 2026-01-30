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
            ->expects($this->once())
            ->method('createFromArrayConfiguration')
            ->with($this->config['cache'])
            ->willReturn($this->createMock(StorageInterface::class));

        $invokedCount = self::exactly(2);
        $this->container
            ->expects($invokedCount)
            ->method('get')
            ->with(self::callback(static function (string $arg) use ($invokedCount): bool {
                switch ($invokedCount->numberOfInvocations()) {
                    case 1:
                        self::assertSame('config', $arg);
                        return true;
                    case 2:
                        self::assertSame(StorageAdapterFactoryInterface::class, $arg);
                        return true;
                    default:
                        return false;
                }
            }))
            ->willReturnOnConsecutiveCalls($this->config, $factory);

        ($this->factory)($this->container);
    }

    public function testWillAssertConfigurationValidity(): void
    {
        $factory = $this->createMock(StorageAdapterFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('assertValidConfigurationStructure')
            ->with($this->config['cache']);

        $factory
            ->expects($this->once())
            ->method('createFromArrayConfiguration')
            ->with($this->config['cache'])
            ->willReturn($this->createMock(StorageInterface::class));

        $invokedCount = self::exactly(2);
        $this->container
            ->expects($invokedCount)
            ->method('get')
            ->with(self::callback(static function (string $arg) use ($invokedCount): bool {
                switch ($invokedCount->numberOfInvocations()) {
                    case 1:
                        self::assertSame('config', $arg);
                        return true;
                    case 2:
                        self::assertSame(StorageAdapterFactoryInterface::class, $arg);
                        return true;
                    default:
                        return false;
                }
            }))
            ->willReturnOnConsecutiveCalls($this->config, $factory);

        ($this->factory)($this->container);
    }
}
