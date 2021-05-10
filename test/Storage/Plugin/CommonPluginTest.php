<?php

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test case
 */

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Plugin\PluginOptions<extended>
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
        $this->assertTrue(
            $pluginManager->has($commonPluginName),
            "Storage plugin name '{$commonPluginName}' not found in storage plugin manager"
        );
    }

    public function testOptionObjectAvailable()
    {
        $options = $this->_plugin->getOptions();
        $this->assertInstanceOf('Laminas\Cache\Storage\Plugin\PluginOptions', $options);
    }

    public function testOptionsGetAndSetDefault()
    {
        $options = $this->_plugin->getOptions();
        $this->_plugin->setOptions($options);
        $this->assertSame($options, $this->_plugin->getOptions());
    }
}
