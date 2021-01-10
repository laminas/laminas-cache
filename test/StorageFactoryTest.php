<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache;

use ErrorException;
use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\Plugin\ClearExpiredByFactor;
use Laminas\Cache\Storage\Plugin\IgnoreUserAbort;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ErrorHandler;
use LaminasTest\Cache\Storage\Adapter\TestAsset\AdapterWithStorageAndEventsCapableInterface;
use PHPUnit\Framework\TestCase;
use Laminas\Cache\Storage\PluginManager;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Exception\RuntimeException;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\StorageFactory
 */
class StorageFactoryTest extends TestCase
{
    public function setUp(): void
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
    }

    public function tearDown(): void
    {
        Cache\StorageFactory::resetAdapterPluginManager();
        Cache\StorageFactory::resetPluginManager();
    }

    public function testDefaultAdapterPluginManager(): void
    {
        $adapters = Cache\StorageFactory::getAdapterPluginManager();
        self::assertInstanceOf(AdapterPluginManager::class, $adapters);
    }

    public function testChangeAdapterPluginManager(): void
    {
        $adapters = new Cache\Storage\AdapterPluginManager(new ServiceManager);
        Cache\StorageFactory::setAdapterPluginManager($adapters);
        self::assertSame($adapters, Cache\StorageFactory::getAdapterPluginManager());
    }

    public function testAdapterFactory(): void
    {
        $adapter1 = Cache\StorageFactory::adapterFactory('Memory');
        self::assertInstanceOf(Memory::class, $adapter1);

        $adapter2 = Cache\StorageFactory::adapterFactory('Memory');
        self::assertInstanceOf(Memory::class, $adapter2);

        self::assertNotSame($adapter1, $adapter2);
    }

    public function testDefaultPluginManager(): void
    {
        $manager = Cache\StorageFactory::getPluginManager();
        self::assertInstanceOf(PluginManager::class, $manager);
    }

    public function testChangePluginManager(): void
    {
        $manager = new Cache\Storage\PluginManager(new ServiceManager);
        Cache\StorageFactory::setPluginManager($manager);
        self::assertSame($manager, Cache\StorageFactory::getPluginManager());
    }

    public function testPluginFactory(): void
    {
        $plugin1 = Cache\StorageFactory::pluginFactory('Serializer');
        self::assertInstanceOf(Serializer::class, $plugin1);

        $plugin2 = Cache\StorageFactory::pluginFactory('Serializer');
        self::assertInstanceOf(Serializer::class, $plugin2);

        self::assertNotSame($plugin1, $plugin2);
    }

    public function testFactoryAdapterAsString(): void
    {
        $cache = Cache\StorageFactory::factory([
            'adapter' => 'Memory',
        ]);
        self::assertInstanceOf(Memory::class, $cache);
    }

    /**
     * @group 4445
     */
    public function testFactoryWithAdapterAsStringAndOptions(): void
    {
        $cache = Cache\StorageFactory::factory([
            'adapter' => 'Memory',
            'options' => [
                'namespace' => 'test'
            ],
        ]);

        self::assertInstanceOf(Memory::class, $cache);
        self::assertSame('test', $cache->getOptions()->getNamespace());
    }

    public function testFactoryAdapterAsArray(): void
    {
        $cache = Cache\StorageFactory::factory([
            'adapter' => [
                'name' => 'Memory',
            ]
        ]);
        self::assertInstanceOf(Memory::class, $cache);
    }

    public function testFactoryWithPlugins(): void
    {
        $adapter = 'Memory';
        $plugins = ['Serializer', 'ClearExpiredByFactor'];

        $cache = Cache\StorageFactory::factory([
            'adapter' => $adapter,
            'plugins' => $plugins,
        ]);

        // test adapter
        self::assertInstanceOf(Memory::class, $cache);

        // test plugin structure
        $i = 0;
        foreach ($cache->getPluginRegistry() as $plugin) {
            self::assertInstanceOf(sprintf(
                'Laminas\Cache\Storage\Plugin\%s',
                $plugins[$i++]
            ), $plugin);
        }
    }

    public function testFactoryInstantiateAdapterWithPluginsWithoutEventsCapableInterfaceThrowsException(): void
    {
        // The BlackHole adapter doesn't implement EventsCapableInterface
        $this->expectException(RuntimeException::class);
        Cache\StorageFactory::factory([
            'adapter' => 'blackhole',
            'plugins' => ['Serializer'],
        ]);
    }

    public function testFactoryWithPluginsAndOptionsArray(): void
    {
        $factory = [
            'adapter' => [
                 'name' => 'Memory',
                 'options' => [
                     'ttl' => 123,
                     'namespace' => 'willBeOverwritten'
                 ],
            ],
            'plugins' => [
                // plugin as a simple string entry
                'Serializer',

                // plugin as name-options pair
                'ClearExpiredByFactor' => [
                    'clearing_factor' => 1,
                ],

                // plugin with full definition
                [
                    'name'     => 'IgnoreUserAbort',
                    'priority' => 100,
                    'options'  => [
                        'exit_on_abort' => false,
                    ],
                ],
            ],
            'options' => [
                'namespace' => 'test',
            ]
        ];
        $storage = Cache\StorageFactory::factory($factory);

        // test adapter
        self::assertInstanceOf(sprintf(
            'Laminas\Cache\Storage\Adapter\%s',
            $factory['adapter']['name']
        ), $storage);
        self::assertEquals(123, $storage->getOptions()->getTtl());
        self::assertEquals('test', $storage->getOptions()->getNamespace());

        // test plugin structure
        foreach ($storage->getPluginRegistry() as $plugin) {
            // test plugin options
            $pluginClass = get_class($plugin);
            switch ($pluginClass) {
                case ClearExpiredByFactor::class:
                    self::assertSame(
                        $factory['plugins']['ClearExpiredByFactor']['clearing_factor'],
                        $plugin->getOptions()->getClearingFactor()
                    );
                    break;

                case IgnoreUserAbort::class:
                    self::assertFalse($plugin->getOptions()->getExitOnAbort());
                    break;

                case Serializer::class:
                    break;

                default:
                    self::fail("Unexpected plugin class '{$pluginClass}'");
            }
        }
    }

    public function testWillTriggerDeprecationWarningForMissingPluginAwareInterface(): void
    {
        $adapters = $this->createMock(Cache\Storage\AdapterPluginManager::class);
        $mock = $this->createMock(AdapterWithStorageAndEventsCapableInterface::class);

        $adapters
            ->expects(self::once())
            ->method('get')
            ->with('Foo')
            ->willReturn($mock);

        $mock
            ->expects(self::once())
            ->method('hasPlugin')
            ->willReturn(false);

        $mock
            ->expects(self::once())
            ->method('addPlugin')
            ->willReturnSelf();

        Cache\StorageFactory::setAdapterPluginManager($adapters);
        ErrorHandler::start(E_USER_DEPRECATED);

        Cache\StorageFactory::factory(['adapter' => 'Foo', 'plugins' => ['IgnoreUserAbort']]);

        $stack = ErrorHandler::stop();
        self::assertInstanceOf(ErrorException::class, $stack);
    }
}
