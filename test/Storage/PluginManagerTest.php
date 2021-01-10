<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager()
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

    protected function getInstanceOf()
    {
        return PluginInterface::class;
    }
}
