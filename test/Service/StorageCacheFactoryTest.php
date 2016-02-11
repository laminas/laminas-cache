<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache\Service;

use Zend\Cache;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * @group      Zend_Cache
 * @covers Zend\Cache\Service\StorageCacheFactory
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
                'CacheFactory' => \Zend\Cache\Service\StorageCacheFactory::class
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
        $this->assertEquals('Zend\Cache\Storage\Adapter\Memory', get_class($cache));
    }
}
