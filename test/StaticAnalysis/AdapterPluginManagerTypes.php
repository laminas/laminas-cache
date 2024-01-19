<?php

declare(strict_types=1);

namespace LaminasTest\Cache\StaticAnalysis;

use Laminas\Cache\Storage\Adapter\Dev\VoidAdapter;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;

final class AdapterPluginManagerTypes
{
    public function willReturnAnAdapterUsingFQCN(AdapterPluginManager $manager): StorageInterface
    {
        return $manager->get(VoidAdapter::class);
    }

    public function validateWillAssertInstanceType(AdapterPluginManager $manager, object $instance): StorageInterface
    {
        $manager->validate($instance);

        return $instance;
    }

    public function buildWillReturnAdapterUsingFQCN(AdapterPluginManager $manager): StorageInterface
    {
        return $manager->build(VoidAdapter::class);
    }
}
