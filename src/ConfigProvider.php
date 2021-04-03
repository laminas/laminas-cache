<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache;

use Laminas\Cache\Service\PatternPluginManagerFactory;
use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Cache\Service\StorageAdapterFactoryFactory;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StorageAdapterPluginManagerFactory;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Service\StoragePluginFactory;
use Laminas\Cache\Service\StoragePluginFactoryFactory;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Laminas\Cache\Service\StoragePluginManagerFactory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\PluginManager;

class ConfigProvider
{
    /**
     * Return default configuration for laminas-cache.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return default service mappings for laminas-cache.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'abstract_factories' => [
                StorageCacheAbstractServiceFactory::class,
            ],
            'factories'          => [
                PatternPluginManager::class  => PatternPluginManagerFactory::class,
                AdapterPluginManager::class  => StorageAdapterPluginManagerFactory::class,
                PluginManager::class         => StoragePluginManagerFactory::class,
                StoragePluginFactory::class  => StoragePluginFactoryFactory::class,
                StorageAdapterFactory::class => StorageAdapterFactoryFactory::class,
            ],
            'aliases'            => [
                StoragePluginFactoryInterface::class  => StoragePluginFactory::class,
                StorageAdapterFactoryInterface::class => StorageAdapterFactory::class,
            ],
        ];
    }
}
