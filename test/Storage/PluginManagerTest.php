<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected static function getPluginManager(array $config = []): AbstractSingleInstancePluginManager
    {
        return new PluginManager(new ServiceManager(), $config);
    }

    protected function getInstanceOf(): string
    {
        return PluginInterface::class;
    }
}
