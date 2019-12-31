<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache;
use Laminas\ServiceManager\ServiceManager;

/**
 * @group      Laminas_Cache
 */
class StorageCacheFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $sm;

    public function setUp()
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
        $this->sm = new ServiceManager();
        $this->sm->setService('Config', ['cache' => [
            'adapter' => 'Memory',
            'plugins' => ['Serializer', 'ClearExpiredByFactor'],
        ]]);
        $this->sm->setFactory('CacheFactory', 'Laminas\Cache\Service\StorageCacheFactory');
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
