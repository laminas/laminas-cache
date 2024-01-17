<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\ConfigProvider;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerGetStorageAdapterFactory(): void
    {
        $configAggregator = new ConfigAggregator([ConfigProvider::class]);
        $config           = $configAggregator->getMergedConfig();
        $container        = new ServiceManager($config['dependencies']);

        // Not calling $container->setService('config', $config) here on purpose

        $result = $container->get(StorageAdapterFactoryInterface::class);
        self::assertInstanceOf(StorageAdapterFactoryInterface::class, $result);
    }
}
