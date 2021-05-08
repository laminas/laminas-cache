<?php

namespace Laminas\Cache;

use Laminas\Cache\Command\DeprecatedStorageFactoryConfigurationCheckCommand;
use Laminas\Cache\Command\DeprecatedStorageFactoryConfigurationCheckCommandFactory;

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
            'laminas-cli'  => $this->getCliConfig(),
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
                Storage\PluginManager::class        => Service\StoragePluginManagerFactory::class,
                DeprecatedStorageFactoryConfigurationCheckCommand::class
                    => DeprecatedStorageFactoryConfigurationCheckCommandFactory::class,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getCliConfig(): array
    {
        return [
            'commands' => [
                DeprecatedStorageFactoryConfigurationCheckCommand::NAME
                    => DeprecatedStorageFactoryConfigurationCheckCommand::class,
            ],
        ];
    }
}
