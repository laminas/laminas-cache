<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\PatternPluginManager;
use Psr\Container\ContainerInterface;

final class PatternPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): PatternPluginManager
    {
        return new PatternPluginManager($container);
    }
}
