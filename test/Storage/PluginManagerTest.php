<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager(): PluginManager
    {
        return new PluginManager(new ServiceManager());
    }

    public function testShareByDefaultAndSharedByDefault()
    {
        self::markTestSkipped('Support for servicemanager v2 is dropped.');
    }

    protected function getV2InvalidPluginException()
    {
        self::fail('Somehow, servicemanager v2 compatibility is being tested.');
    }

    protected function getInstanceOf(): string
    {
        return PluginInterface::class;
    }
}
