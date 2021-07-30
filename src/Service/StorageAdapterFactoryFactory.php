<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\Storage\AdapterPluginManager;
use Psr\Container\ContainerInterface;

final class StorageAdapterFactoryFactory
{
    public function __invoke(ContainerInterface $container): StorageAdapterFactory
    {
        return new StorageAdapterFactory(
            $container->get(AdapterPluginManager::class),
            $container->get(StoragePluginFactoryInterface::class)
        );
    }
}
