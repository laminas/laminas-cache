<?php

declare(strict_types=1);

namespace LaminasTest\Cache\StaticAnalysis;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\PluginManager;

final class PluginManagerTypes
{
    public function willReturnAPluginUsingFQCN(PluginManager $manager): PluginInterface
    {
        return $manager->get(Serializer::class);
    }

    public function validateWillAssertInstanceType(PluginManager $manager, object $instance): PluginInterface
    {
        $manager->validate($instance);

        return $instance;
    }

    public function buildWillReturnPluginUsingFQCN(PluginManager $manager): PluginInterface
    {
        return $manager->build(Serializer::class);
    }
}
