<?php

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\PluginManager;
use Psr\Container\ContainerInterface;

final class StoragePluginManagerFactory
{
    public function __invoke(ContainerInterface $container): PluginManager
    {
        return new PluginManager($container);
    }
}
