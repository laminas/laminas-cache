<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\StorageFactory<extended>
 */
class StorageCacheAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $sm;

    public function setUp()
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
        $config = [
            'services' => [
                'config' => [
                    'caches' => [
                        'Memory' => [
                            'adapter' => 'Memory',
                            'plugins' => ['Serializer', 'ClearExpiredByFactor'],
                        ],
                        'Foo' => [
                            'adapter' => 'Memory',
                            'plugins' => ['Serializer', 'ClearExpiredByFactor'],
                        ],
                    ]
                ],
            ],
            'abstract_factories' => [
                'Laminas\Cache\Service\StorageCacheAbstractServiceFactory'
            ]
        ];
        $this->sm = new ServiceManager();
        if (method_exists($this->sm, 'configure')) {
            // v3
            $this->sm->configure($config);
        } else {
            // v2
            $config = new Config($config);
            $config->configureServiceManager($this->sm);
        }
    }

    public function tearDown()
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
    }

    public function testCanLookupCacheByName()
    {
        $this->assertTrue($this->sm->has('Memory'));
        $this->assertTrue($this->sm->has('Foo'));
    }

    public function testCanRetrieveCacheByName()
    {
        $cacheA = $this->sm->get('Memory');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cacheA);

        $cacheB = $this->sm->get('Foo');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cacheB);

        $this->assertNotSame($cacheA, $cacheB);
    }

    public function testInvalidCacheServiceNameWillBeIgnored()
    {
        $this->assertFalse($this->sm->has('invalid'));
    }
}
