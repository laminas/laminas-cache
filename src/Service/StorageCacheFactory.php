<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Storage cache factory.
 */
class StorageCacheFactory implements FactoryInterface
{
    use PluginManagerLookupTrait;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->prepareStorageFactory($container);

        $config = $container->get('config');
        $cacheConfig = isset($config['cache']) ? $config['cache'] : [];
        return StorageFactory::factory($cacheConfig);
    }
}
