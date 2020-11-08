<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Laminas\Cache\Storage\Plugin\PluginOptions;

/**
 * PHPUnit test case
 */

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Plugin\PluginOptions<extended>
 */
abstract class CommonPluginTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * The storage plugin
     *
     * @var \Laminas\Cache\Storage\Plugin\PluginInterface
     */
    protected $_plugin;
    // @codingStandardsIgnoreEnd

    /**
     * A data provider for common storage plugin names
     */
    abstract public function getCommonPluginNamesProvider();

    /**
     * @dataProvider getCommonPluginNamesProvider
     */
    public function testPluginManagerWithCommonNames($commonPluginName)
    {
        $pluginManager = new PluginManager(new ServiceManager);
        self::assertTrue(
            $pluginManager->has($commonPluginName),
            "Storage plugin name '{$commonPluginName}' not found in storage plugin manager"
        );
    }

    public function testOptionObjectAvailable(): void
    {
        $options = $this->_plugin->getOptions();
        self::assertInstanceOf(PluginOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault(): void
    {
        $options = $this->_plugin->getOptions();
        $this->_plugin->setOptions($options);
        self::assertSame($options, $this->_plugin->getOptions());
    }
}
