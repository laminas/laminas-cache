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
 * @covers \Laminas\Cache\Service\StorageCacheFactory
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

    public function testCreateServiceCache(): void
    {
        $cache = $this->sm->get('CacheFactory');
        self::assertEquals(Memory::class, get_class($cache));
    }

    public function testSetsFactoryAdapterPluginManagerInstanceOnInvocation(): void
    {
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter
            ->expects(self::never())
            ->method('setOptions');
        $adapter
            ->expects(self::never())
            ->method('hasPlugin');
        $adapter
            ->expects(self::never())
            ->method('addPlugin');

        $adapterPluginManager = $this->createMock(AdapterPluginManager::class);
        $adapterPluginManager
            ->expects(self::once())
            ->method('get')
            ->with('Memory')
            ->willReturn($adapter);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([AdapterPluginManager::class], [PluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls(true, false, true);

        $container
            ->method('get')
            ->withConsecutive([AdapterPluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls($adapterPluginManager, [
                'cache' => [ 'adapter' => 'Memory' ],
            ]);

        $factory = new StorageCacheFactory();
        self::assertSame($adapter, $factory($container, 'Cache'));
        self::assertSame($adapterPluginManager, StorageFactory::getAdapterPluginManager());
    }

    public function testSetsFactoryPluginManagerInstanceOnInvocation(): void
    {
        $plugin = $this->createMock(PluginInterface::class);
        $plugin
            ->expects(self::never())
            ->method('setOptions');

        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager
            ->method('get')
            ->with('Serializer')
            ->willReturn($plugin);

        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter
            ->expects(self::never())
            ->method('setOptions');
        $adapter
            ->expects(self::once())
            ->method('hasPlugin')
            ->with($plugin)
            ->willReturn(false);

        $adapter
            ->expects(self::once())
            ->method('addPlugin')
            ->with($plugin);

        $adapterPluginManager = $this->createMock(AdapterPluginManager::class);
        $adapterPluginManager
            ->expects(self::once())
            ->method('get')
            ->with('Memory')
            ->willReturn($adapter);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([AdapterPluginManager::class], [PluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls(true, true, true);

        $container
            ->method('get')
            ->withConsecutive([AdapterPluginManager::class], [PluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls($adapterPluginManager, $pluginManager, [
                'cache' => [
                    'adapter' => 'Memory',
                    'plugins' => ['Serializer'],
                ],
            ]);

        $factory = new StorageCacheFactory();
        $factory($container, 'Cache');
        self::assertSame($pluginManager, StorageFactory::getPluginManager());
    }
}
