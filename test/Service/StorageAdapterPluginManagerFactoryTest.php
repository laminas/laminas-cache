<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StorageAdapterPluginManagerFactory;
use Laminas\Cache\Storage\AdapterPluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StorageAdapterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new StorageAdapterPluginManagerFactory();

        $adapters = $factory($container);
        self::assertInstanceOf(AdapterPluginManager::class, $adapters);
    }
}
