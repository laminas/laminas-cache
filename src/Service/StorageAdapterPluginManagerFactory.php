<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;

class StorageAdapterPluginManagerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return AdapterPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new AdapterPluginManager($container, $options ?: []);
    }
}
