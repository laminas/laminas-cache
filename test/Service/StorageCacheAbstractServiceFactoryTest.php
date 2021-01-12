<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginManager;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class StorageCacheAbstractServiceFactoryTest extends TestCase
{
    /** @var ServiceManager */
    protected $sm;

    public function setUp(): void
    {
        StorageFactory::resetAdapterPluginManager();
        StorageFactory::resetPluginManager();
        $config   = [
            'services'           => [
                'config' => [
                    'caches' => [
                        'Memory' => [
                            'adapter' => 'Memory',
                            'plugins' => ['Serializer', 'ClearExpiredByFactor'],
                        ],
                        'Foo'    => [
                            'adapter' => 'Memory',
                            'plugins' => ['Serializer', 'ClearExpiredByFactor'],
                        ],
                    ],
                ],
            ],
            'abstract_factories' => [
                StorageCacheAbstractServiceFactory::class,
            ],
        ];
        $this->sm = new ServiceManager();
        (new Config($config))->configureServiceManager($this->sm);
    }

    public function tearDown(): void
    {
        StorageFactory::resetAdapterPluginManager();
        StorageFactory::resetPluginManager();
    }

    public function testCanLookupCacheByName(): void
    {
        self::assertTrue($this->sm->has('Memory'));
        self::assertTrue($this->sm->has('Foo'));
    }

    public function testCanRetrieveCacheByName(): void
    {
        $cacheA = $this->sm->get('Memory');
        self::assertInstanceOf(Memory::class, $cacheA);

        $cacheB = $this->sm->get('Foo');
        self::assertInstanceOf(Memory::class, $cacheB);

        self::assertNotSame($cacheA, $cacheB);
    }

    public function testInvalidCacheServiceNameWillBeIgnored(): void
    {
        self::assertFalse($this->sm->has('invalid'));
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
            ->withConsecutive(
                [AdapterPluginManager::class],
                [PluginManager::class],
                ['config']
            )
            ->willReturnOnConsecutiveCalls(true, false, true);

        $container
            ->method('get')
            ->withConsecutive([AdapterPluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls($adapterPluginManager, [
                'caches' => ['Cache' => ['adapter' => 'Memory']],
            ]);

        $factory = new StorageCacheAbstractServiceFactory();
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
            ->expects(self::once())
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
            ->willReturn(true);

        $container
            ->method('get')
            ->withConsecutive([AdapterPluginManager::class], [PluginManager::class], ['config'])
            ->willReturnOnConsecutiveCalls($adapterPluginManager, $pluginManager, [
                'caches' => [
                    'Cache' => [
                        'adapter' => 'Memory',
                        'plugins' => ['Serializer'],
                    ],
                ],
            ]);

        $factory = new StorageCacheAbstractServiceFactory();
        $factory($container, 'Cache');
        self::assertSame($pluginManager, StorageFactory::getPluginManager());
    }
}
