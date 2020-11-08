<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageCacheFactory;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers Laminas\Cache\Service\StorageCacheFactory
 */
class StorageCacheFactoryTest extends TestCase
{
    protected $sm;

    public function setUp(): void
    {
        StorageFactory::resetAdapterPluginManager();
        StorageFactory::resetPluginManager();
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
                'CacheFactory' => StorageCacheFactory::class
            ]
        ];
        $this->sm = new ServiceManager();
        (new Config($config))->configureServiceManager($this->sm);
    }

    public function tearDown(): void
    {
        StorageFactory::resetAdapterPluginManager();
        StorageFactory::resetPluginManager();
    }

    public function testCreateServiceCache()
    {
        $cache = $this->sm->get('CacheFactory');
        $this->assertEquals(Memory::class, get_class($cache));
    }

    public function testSetsFactoryAdapterPluginManagerInstanceOnInvocation()
    {
        $adapter = $this->prophesize(AbstractAdapter::class);
        $adapter->willImplement(StorageInterface::class);
        $adapter->setOptions(Argument::any())->shouldNotBeCalled();
        $adapter->hasPlugin(Argument::any(), Argument::any())->shouldNotBeCalled();
        $adapter->addPlugin(Argument::any(), Argument::any())->shouldNotBeCalled();

        $adapterPluginManager = $this->prophesize(AdapterPluginManager::class);
        $adapterPluginManager->get('Memory')->willReturn($adapter->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(AdapterPluginManager::class)->willReturn(true);
        $container->get(AdapterPluginManager::class)->willReturn($adapterPluginManager->reveal());
        $container->has(PluginManager::class)->willReturn(false);
        $container->has(\Zend\Cache\Storage\PluginManager::class)->willReturn(false);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'cache' => [ 'adapter' => 'Memory' ],
        ]);

        $factory = new StorageCacheFactory();
        $this->assertSame($adapter->reveal(), $factory($container->reveal(), 'Cache'));
        $this->assertSame($adapterPluginManager->reveal(), StorageFactory::getAdapterPluginManager());
    }

    public function testSetsFactoryPluginManagerInstanceOnInvocation()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->setOptions(Argument::any())->shouldNotBeCalled();

        $pluginManager = $this->prophesize(PluginManager::class);
        $pluginManager->get('Serializer')->willReturn($plugin->reveal());

        $adapter = $this->prophesize(AbstractAdapter::class);
        $adapter->willImplement(StorageInterface::class);
        $adapter->setOptions(Argument::any())->shouldNotBeCalled();
        $adapter->hasPlugin($plugin->reveal(), Argument::any())->willReturn(false);
        $adapter->addPlugin($plugin->reveal(), Argument::any())->shouldBeCalled();

        $adapterPluginManager = $this->prophesize(AdapterPluginManager::class);
        $adapterPluginManager->get('Memory')->willReturn($adapter->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(AdapterPluginManager::class)->willReturn(true);
        $container->get(AdapterPluginManager::class)->willReturn($adapterPluginManager->reveal());
        $container->has(PluginManager::class)->willReturn(true);
        $container->get(PluginManager::class)->willReturn($pluginManager->reveal());

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'cache' => [
                'adapter' => 'Memory',
                'plugins' => ['Serializer'],
            ],
        ]);

        $factory = new StorageCacheFactory();
        $factory($container->reveal(), 'Cache');
        $this->assertSame($pluginManager->reveal(), StorageFactory::getPluginManager());
    }
}
