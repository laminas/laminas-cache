<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

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
