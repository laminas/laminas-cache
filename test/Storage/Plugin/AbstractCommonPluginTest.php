<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractCommonPluginTest extends TestCase
{
    protected PluginInterface $plugin;

    /**
     * A data provider for common storage plugin names
     *
     * @return iterable<array-key,array{0:string}>
     */
    abstract public function getCommonPluginNamesProvider(): iterable;

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
