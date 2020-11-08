<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageAdapterPluginManagerFactory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class StorageAdapterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new StorageAdapterPluginManagerFactory();

        $adapters = $factory($container, AdapterPluginManager::class);
        self::assertInstanceOf(AdapterPluginManager::class, $adapters);
    }

    public function testFactoryConfiguresPluginManagerUnderContainerInterop(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $adapter = $this->createMock(StorageInterface::class);

        $factory = new StorageAdapterPluginManagerFactory();
        $adapters = $factory($container, AdapterPluginManager::class, [
            'services' => [
                'test' => $adapter,
            ],
        ]);
        self::assertSame($adapter, $adapters->get('test'));
    }
}
