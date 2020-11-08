<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PatternPluginManagerFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     *
     * @return PatternPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new PatternPluginManager($container, $options ?: []);
    }
}
