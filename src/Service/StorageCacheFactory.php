<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Cache\StorageFactory;
use Psr\Container\ContainerInterface;

/**
 * Storage cache factory.
 */
final class StorageCacheFactory
{
    use PluginManagerLookupTrait;

    public function __invoke(ContainerInterface $container): StorageInterface
    {
        $this->prepareStorageFactory($container);

        $cacheConfig = $container->get('config')['cache'] ?? [];
        return StorageFactory::factory($cacheConfig);
    }
}
