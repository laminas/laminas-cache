<?php

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Exception\RuntimeException;
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

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return PluginInterface::class;
    }
}
