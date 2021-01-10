<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

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
