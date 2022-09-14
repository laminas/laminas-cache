<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\PluginManager;
use Psr\Container\ContainerInterface;

final class StoragePluginFactoryFactory
{
    public function __invoke(ContainerInterface $container): StoragePluginFactory
    {
        return new StoragePluginFactory($container->get(PluginManager::class));
    }
}
