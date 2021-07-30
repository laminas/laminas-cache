<?php

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Cache\StorageFactory;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

/**
 * Storage cache factory.
 */
final class StorageCacheFactory
{
    use PluginManagerLookupTrait;

    public const CACHE_CONFIGURATION_KEY = 'cache';

    public function __invoke(ContainerInterface $container): StorageInterface
    {
        $this->prepareStorageFactory($container);

        $config = $container->get('config');
        Assert::isArrayAccessible($config);
        $cacheConfig = $config['cache'] ?? [];
        Assert::isMap($cacheConfig);
        return StorageFactory::factory($cacheConfig);
    }
}
