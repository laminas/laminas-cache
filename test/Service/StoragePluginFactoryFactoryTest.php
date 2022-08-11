<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StoragePluginFactoryFactory;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StoragePluginFactoryFactoryTest extends TestCase
{
    private StoragePluginFactoryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new StoragePluginFactoryFactory();
    }

    public function testWillRetrieveDependenciesFromContainer(): void
    {
        $plugins   = $this->createMock(PluginManagerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('get')
            ->with(PluginManager::class)
            ->willReturn($plugins);

        ($this->factory)($container);
    }
}
