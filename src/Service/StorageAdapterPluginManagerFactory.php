<?php

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\AdapterPluginManager;
use Psr\Container\ContainerInterface;

final class StorageAdapterPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): AdapterPluginManager
    {
        return new AdapterPluginManager($container);
    }
}
