<?php

declare(strict_types=1);

namespace LaminasTest\Cache\StaticAnalysis;

use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;

final class AdapterPluginManagerTypes
{
    public function willReturnAnAdapterUsingFQCN(AdapterPluginManager $manager): StorageInterface
    {
        return $manager->get(MockAdapter::class);
    }

    public function validateWillAssertInstanceType(AdapterPluginManager $manager, object $instance): StorageInterface
    {
        $manager->validate($instance);

        return $instance;
    }

    public function buildWillReturnAdapterUsingFQCN(AdapterPluginManager $manager): StorageInterface
    {
        return $manager->build(MockAdapter::class);
    }
}
