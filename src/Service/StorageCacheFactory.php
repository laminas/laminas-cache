<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

final class StorageCacheFactory
{
    public function __invoke(ContainerInterface $container): StorageInterface
    {
        $factory = $container->get(StorageAdapterFactoryInterface::class);

        $config = $container->get('config');
        Assert::isArrayAccessible($config);
        $cacheConfig = $config['cache'] ?? [];
        Assert::isMap($cacheConfig);
        $factory->assertValidConfigurationStructure($cacheConfig);

        return $factory->createFromArrayConfiguration($cacheConfig);
    }
}
