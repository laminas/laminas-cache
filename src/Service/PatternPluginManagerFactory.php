<?php

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
