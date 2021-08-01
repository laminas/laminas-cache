<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StorageAdapterFactoryFactory;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StorageAdapterFactoryFactoryTest extends TestCase
{
    /** @var StorageAdapterFactoryFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new StorageAdapterFactoryFactory();
    }

    public function testWillRetrieveDependenciesFromContainer(): void
    {
        $adapters      = $this->createMock(PluginManagerInterface::class);
        $pluginFactory = $this->createMock(StoragePluginFactoryInterface::class);
        $container     = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([AdapterPluginManager::class], [StoragePluginFactoryInterface::class])
            ->willReturnOnConsecutiveCalls($adapters, $pluginFactory);

        ($this->factory)($container);
    }
}
