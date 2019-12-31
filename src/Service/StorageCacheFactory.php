<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Storage cache factory.
 */
class StorageCacheFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // Configure the cache
        $config = $serviceLocator->get('Config');
        $cacheConfig = isset($config['cache']) ? $config['cache'] : [];
        $cache = StorageFactory::factory($cacheConfig);

        return $cache;
    }
}
