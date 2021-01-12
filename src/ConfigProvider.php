<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache;

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
                Service\StorageCacheAbstractServiceFactory::class,
            ],
            'factories'          => [
                PatternPluginManager::class         => Service\PatternPluginManagerFactory::class,
                Storage\AdapterPluginManager::class => Service\StorageAdapterPluginManagerFactory::class,
                Storage\PluginManager::class        => Service\StoragePluginManagerFactory::class,
            ],
        ];
    }
}
