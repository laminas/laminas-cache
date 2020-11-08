<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StoragePluginManagerFactory;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

class StoragePluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new StoragePluginManagerFactory();

        $plugins = $factory($container, PluginManager::class);
        self::assertInstanceOf(PluginManager::class, $plugins);
    }

    public function testFactoryConfiguresPluginManagerUnderContainerInterop(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $plugin = $this->createMock(PluginInterface::class);

        $factory = new StoragePluginManagerFactory();
        $plugins = $factory($container, PluginManager::class, [
            'services' => [
                'test' => $plugin,
            ],
        ]);
        self::assertSame($plugin, $plugins->get('test'));
    }
}
