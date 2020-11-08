<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;

class StoragePluginManagerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return PluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new PluginManager($container, $options ?: []);
    }
}
