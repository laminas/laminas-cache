<?php

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractCommonPluginTest extends TestCase
{
    /** @var PluginInterface */
    protected $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        if (! $this->plugin instanceof PluginInterface) {
            self::fail('Cannot detect plugin instance.');
        }
    }

    /**
     * A data provider for common storage plugin names
     */
    abstract public function getCommonPluginNamesProvider();

    /**
     * @dataProvider getCommonPluginNamesProvider
     */
    public function testPluginManagerWithCommonNames(string $commonPluginName): void
    {
        $pluginManager = new PluginManager(new ServiceManager());
        self::assertTrue(
            $pluginManager->has($commonPluginName),
            "Storage plugin name '{$commonPluginName}' not found in storage plugin manager"
        );
    }

    public function testOptionObjectAvailable()
    {
        $options = $this->plugin->getOptions();
        self::assertInstanceOf(PluginOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault()
    {
        $options = $this->plugin->getOptions();
        $this->plugin->setOptions($options);
        self::assertSame($options, $this->plugin->getOptions());
    }
}
