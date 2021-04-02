<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache;

use Laminas\Cache;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\PatternFactory
 */
class PatternFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        Cache\PatternFactory::resetPluginManager();
    }

    public function tearDown(): void
    {
        Cache\PatternFactory::resetPluginManager();
    }

    public function testDefaultPluginManager()
    {
        $plugins = Cache\PatternFactory::getPluginManager();
        $this->assertInstanceOf('Laminas\Cache\PatternPluginManager', $plugins);
    }

    public function testChangePluginManager()
    {
        $plugins = new Cache\PatternPluginManager(
            $this->getMockBuilder('Interop\Container\ContainerInterface')->getMock()
        );
        Cache\PatternFactory::setPluginManager($plugins);
        $this->assertSame($plugins, Cache\PatternFactory::getPluginManager());
    }

    public function testFactory()
    {
        $pattern1 = Cache\PatternFactory::factory('capture');
        $this->assertInstanceOf('Laminas\Cache\Pattern\CaptureCache', $pattern1);

        $pattern2 = Cache\PatternFactory::factory('capture');
        $this->assertInstanceOf('Laminas\Cache\Pattern\CaptureCache', $pattern2);

        $this->assertNotSame($pattern1, $pattern2);
    }
}
