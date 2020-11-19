<?php

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StorageCacheAbstractServiceFactoryTest extends TestCase
{
    /** @var StorageCacheAbstractServiceFactory */
    private $factory;

    /** @var StorageAdapterFactoryInterface&MockObject */
    private $adapterFactory;

    /** @var array<string,array{Foo:array,Memory:array}> */
    private $config = [
        'caches' => [
            'Memory' => [
                'adapter' => 'Memory',
                'plugins' => ['Serializer', 'ClearExpiredByFactor'],
            ],
            'Foo'    => [
                'adapter' => 'Memory',
                'plugins' => ['Serializer', 'ClearExpiredByFactor'],
            ],
        ],
    ];

    /** @var ContainerInterface&MockObject */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory        = new StorageCacheAbstractServiceFactory();
        $this->adapterFactory = $this->createMock(StorageAdapterFactoryInterface::class);
        $this->container      = $this->createMock(ContainerInterface::class);
        $this->container
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', $this->config],
                [StorageAdapterFactoryInterface::class, $this->adapterFactory],
            ]);
    }

    public function testCanLookupCacheByName(): void
    {
        self::assertTrue($this->factory->canCreate($this->container, 'Memory'));
        self::assertTrue($this->factory->canCreate($this->container, 'Foo'));
    }

    public function testCanRetrieveCacheByName(): void
    {
        $this->adapterFactory
            ->expects(self::once())
            ->method('createFromArrayConfiguration')
            ->with($this->config['caches']['Memory'])
            ->willReturn($this->createMock(StorageInterface::class));

        ($this->factory)($this->container, 'Memory');
    }

    public function testWillAssertConfigurationValidity(): void
    {
        $this->adapterFactory
            ->expects(self::once())
            ->method('assertValidConfigurationStructure')
            ->with($this->config['caches']['Foo']);

        ($this->factory)($this->container, 'Foo');
    }

    public function testWillPassInvalidArgumentExceptionFromConfigurationValidityAssertion(): void
    {
        $exception = new InvalidArgumentException();
        $this->adapterFactory
            ->expects(self::once())
            ->method('assertValidConfigurationStructure')
            ->willThrowException($exception);

        $this->expectExceptionObject($exception);
        ($this->factory)($this->container, 'Foo');
    }

    public function testInvalidCacheServiceNameWillBeIgnored(): void
    {
        self::assertFalse(
            $this->factory->canCreate($this->container, 'non existent configuration')
        );
    }
}
