<?php

namespace Laminas\Cache;

use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Cache\Service\StorageAdapterFactoryFactory;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StoragePluginFactory;
use Laminas\Cache\Service\StoragePluginFactoryFactory;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Laminas\ServiceManager\ServiceManager;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class ConfigProvider
{
    public const string ADAPTER_PLUGIN_MANAGER_CONFIGURATION_KEY = 'storage_adapters';

    /**
     * Return default configuration for laminas-cache.
     *
     * @return array{dependencies:ServiceManagerConfiguration,...<string,mixed>}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return default service mappings for laminas-cache.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencyConfig(): array
    {
        return [
            'abstract_factories' => [
                Service\StorageCacheAbstractServiceFactory::class,
            ],
            'factories'          => [
                Storage\AdapterPluginManager::class   => Service\StorageAdapterPluginManagerFactory::class,
                Storage\PluginManager::class          => Service\StoragePluginManagerFactory::class,
                StoragePluginFactory::class           => StoragePluginFactoryFactory::class,
                StoragePluginFactoryInterface::class  => StoragePluginFactoryFactory::class,
                StorageAdapterFactory::class          => StorageAdapterFactoryFactory::class,
                StorageAdapterFactoryInterface::class => StorageAdapterFactoryFactory::class,
            ],
        ];
    }
}
