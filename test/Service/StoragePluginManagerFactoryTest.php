<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StoragePluginManagerFactory;
use Laminas\Cache\Storage\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StoragePluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new StoragePluginManagerFactory();

        $plugins = $factory($container);
        self::assertInstanceOf(PluginManager::class, $plugins);
    }
}
