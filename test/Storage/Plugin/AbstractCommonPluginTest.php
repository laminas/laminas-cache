<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test case
 */

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Plugin\PluginOptions<extended>
 */
abstract class AbstractCommonPluginTest extends TestCase
{
    /**
     * The storage plugin
     *
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * A data provider for common storage plugin names
     *
     * @return iterable<string,array{0:string}>
     */
    abstract public function getCommonPluginNamesProvider();

    /**
     * @dataProvider getCommonPluginNamesProvider
     */
    public function testPluginManagerWithCommonNames(string $commonPluginName)
    {
        $pluginManager = new PluginManager(new ServiceManager());
        self::assertTrue(
            $pluginManager->has($commonPluginName),
            "Storage plugin name '{$commonPluginName}' not found in storage plugin manager"
        );
    }

    public function testOptionObjectAvailable(): void
    {
        $options = $this->plugin->getOptions();
        self::assertInstanceOf(PluginOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault(): void
    {
        $options = $this->plugin->getOptions();
        $this->plugin->setOptions($options);
        self::assertSame($options, $this->plugin->getOptions());
    }
}
