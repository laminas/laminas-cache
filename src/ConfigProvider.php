<?php

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
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Cache\PatternPluginManager::class => PatternPluginManager::class,
                \Zend\Cache\Storage\AdapterPluginManager::class => Storage\AdapterPluginManager::class,
                \Zend\Cache\Storage\PluginManager::class => Storage\PluginManager::class,
            ],
            'abstract_factories' => [
                Service\StorageCacheAbstractServiceFactory::class,
            ],
            'factories' => [
                PatternPluginManager::class => Service\PatternPluginManagerFactory::class,
                Storage\AdapterPluginManager::class => Service\StorageAdapterPluginManagerFactory::class,
                Storage\PluginManager::class => Service\StoragePluginManagerFactory::class,
            ],
        ];
    }
}
