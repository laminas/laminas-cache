<?php

namespace LaminasTest\Cache;

use Interop\Container\ContainerInterface;
use Laminas\Cache;
use Laminas\Cache\Pattern\CaptureCache;
use Laminas\Cache\PatternPluginManager;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\PatternFactory
 */
class PatternFactoryTest extends TestCase
{
    public function setUp(): void
    {
        Cache\PatternFactory::resetPluginManager();
    }

    public function tearDown(): void
    {
        Cache\PatternFactory::resetPluginManager();
    }

    public function testDefaultPluginManager(): void
    {
        $plugins = Cache\PatternFactory::getPluginManager();
        self::assertInstanceOf(PatternPluginManager::class, $plugins);
    }

    public function testChangePluginManager(): void
    {
        $plugins = new Cache\PatternPluginManager(
            $this->createMock(ContainerInterface::class)
        );
        Cache\PatternFactory::setPluginManager($plugins);
        self::assertSame($plugins, Cache\PatternFactory::getPluginManager());
    }

    public function testFactory(): void
    {
        $pattern1 = Cache\PatternFactory::factory('capture');
        self::assertInstanceOf(CaptureCache::class, $pattern1);

        $pattern2 = Cache\PatternFactory::factory('capture');
        self::assertInstanceOf(CaptureCache::class, $pattern2);

        self::assertNotSame($pattern1, $pattern2);
    }
}
