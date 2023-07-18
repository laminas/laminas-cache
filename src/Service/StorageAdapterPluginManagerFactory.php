<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class StorageAdapterPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): AdapterPluginManager
    {
        $pluginManager = new AdapterPluginManager($container);

        // If we do not have a config service, nothing more to do
        if (! $container->has('config')) {
            return $pluginManager;
        }

        $config = $container->get('config');

        // If we do not have a configuration, nothing more to do
        if (! isset($config[AdapterPluginManager::CONFIGURATION_KEY]) || ! is_array($config[AdapterPluginManager::CONFIGURATION_KEY])) {
            return $pluginManager;
        }

        // Wire service configuration
        /** @var ServiceManagerConfiguration $config */
        $config = $config[AdapterPluginManager::CONFIGURATION_KEY];
        $pluginManager->configure($config);

        return $pluginManager;
    }
}
