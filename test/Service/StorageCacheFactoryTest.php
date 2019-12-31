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
 * @covers Laminas\Cache\Service\StorageCacheFactory
 */
class StorageCacheFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $sm;

    public function setUp()
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
        $config = [
            'services' => [
                'config' => [
                    'cache' => [
                        'adapter' => 'Memory',
                        'plugins' => ['Serializer', 'ClearExpiredByFactor'],
                    ]
                ]
            ],
            'factories' => [
                'CacheFactory' => \Laminas\Cache\Service\StorageCacheFactory::class
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

    public function testCreateServiceCache()
    {
        $cache = $this->sm->get('CacheFactory');
        $this->assertEquals('Laminas\Cache\Storage\Adapter\Memory', get_class($cache));
    }
}
