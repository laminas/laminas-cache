<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache;

use ErrorException;
use Laminas\Cache;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ErrorHandler;
use LaminasTest\Cache\Storage\Adapter\TestAsset\AdapterWithStorageAndEventsCapableInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\StorageFactory
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
        $this->assertInstanceOf('Laminas\Cache\Storage\AdapterPluginManager', $adapters);
    }

    public function testChangeAdapterPluginManager(): void
    {
        $adapters = new Cache\Storage\AdapterPluginManager(new ServiceManager);
        Cache\StorageFactory::setAdapterPluginManager($adapters);
        $this->assertSame($adapters, Cache\StorageFactory::getAdapterPluginManager());
    }

    public function testAdapterFactory(): void
    {
        $adapter1 = Cache\StorageFactory::adapterFactory('Memory');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $adapter1);

        $adapter2 = Cache\StorageFactory::adapterFactory('Memory');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $adapter2);

        $this->assertNotSame($adapter1, $adapter2);
    }

    public function testDefaultPluginManager(): void
    {
        $manager = Cache\StorageFactory::getPluginManager();
        $this->assertInstanceOf('Laminas\Cache\Storage\PluginManager', $manager);
    }

    public function testChangePluginManager(): void
    {
        $manager = new Cache\Storage\PluginManager(new ServiceManager);
        Cache\StorageFactory::setPluginManager($manager);
        $this->assertSame($manager, Cache\StorageFactory::getPluginManager());
    }

    public function testPluginFactory(): void
    {
        $plugin1 = Cache\StorageFactory::pluginFactory('Serializer');
        $this->assertInstanceOf('Laminas\Cache\Storage\Plugin\Serializer', $plugin1);

        $plugin2 = Cache\StorageFactory::pluginFactory('Serializer');
        $this->assertInstanceOf('Laminas\Cache\Storage\Plugin\Serializer', $plugin2);

        $this->assertNotSame($plugin1, $plugin2);
    }

    public function testFactoryAdapterAsString(): void
    {
        $cache = Cache\StorageFactory::factory([
            'adapter' => 'Memory',
        ]);
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cache);
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

        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cache);
        $this->assertSame('test', $cache->getOptions()->getNamespace());
    }

    public function testFactoryAdapterAsArray(): void
    {
        $cache = Cache\StorageFactory::factory([
            'adapter' => [
                'name' => 'Memory',
            ]
        ]);
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cache);
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
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cache);

        // test plugin structure
        $i = 0;
        foreach ($cache->getPluginRegistry() as $plugin) {
            $this->assertInstanceOf('Laminas\Cache\Storage\Plugin\\' . $plugins[$i++], $plugin);
        }
    }

    public function testFactoryInstantiateAdapterWithPluginsWithoutEventsCapableInterfaceThrowsException(): void
    {
        // The BlackHole adapter doesn't implement EventsCapableInterface
        $this->expectException('Laminas\Cache\Exception\RuntimeException');
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
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\\' . $factory['adapter']['name'], $storage);
        $this->assertEquals(123, $storage->getOptions()->getTtl());
        $this->assertEquals('test', $storage->getOptions()->getNamespace());

        // test plugin structure
        foreach ($storage->getPluginRegistry() as $plugin) {
            // test plugin options
            $pluginClass = get_class($plugin);
            switch ($pluginClass) {
                case 'Laminas\Cache\Storage\Plugin\ClearExpiredByFactor':
                    $this->assertSame(
                        $factory['plugins']['ClearExpiredByFactor']['clearing_factor'],
                        $plugin->getOptions()->getClearingFactor()
                    );
                    break;

                case 'Laminas\Cache\Storage\Plugin\Serializer':
                    break;

                case 'Laminas\Cache\Storage\Plugin\IgnoreUserAbort':
                    $this->assertFalse($plugin->getOptions()->getExitOnAbort());
                    break;

                default:
                    $this->fail("Unexpected plugin class '{$pluginClass}'");
            }
        }
    }

    public function testWillTriggerDeprecationWarningForMissingPluginAwareInterface(): void
    {
        $adapters = $this->prophesize(Cache\Storage\AdapterPluginManager::class);

        $adapters->get('Foo')->willReturn(new AdapterWithStorageAndEventsCapableInterface());

        Cache\StorageFactory::setAdapterPluginManager($adapters->reveal());
        ErrorHandler::start(E_USER_DEPRECATED);

        Cache\StorageFactory::factory(['adapter' => 'Foo', 'plugins' => ['IgnoreUserAbort']]);

        $stack = ErrorHandler::stop();
        $this->assertInstanceOf(ErrorException::class, $stack);
    }
}
