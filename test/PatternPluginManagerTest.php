<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache;

use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;

class PatternPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager()
    {
        return new PatternPluginManager(new ServiceManager());
    }

    protected function getInstanceOf()
    {
        return PatternInterface::class;
    }

    public function testGetWillInjectProvidedOptionsAsPatternOptionsInstance(): void
    {
        $plugins = $this->getPluginManager();
        $storage = $this->createMock(StorageInterface::class);
        $plugin = $plugins->get('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        self::assertFalse($options->getCacheOutput());
        self::assertSame($storage, $options->getStorage());
    }

    public function testBuildWillInjectProvidedOptionsAsPatternOptionsInstance(): void
    {
        $plugins = $this->getPluginManager();

        $storage = $this->createMock(StorageInterface::class);
        $plugin = $plugins->build('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        self::assertFalse($options->getCacheOutput());
        self::assertSame($storage, $options->getStorage());
    }

    public function testShareByDefaultAndSharedByDefault()
    {
        self::markTestSkipped('Support for servicemanager v2 is dropped.');
    }

    protected function getV2InvalidPluginException()
    {
        self::fail('Somehow, servicemanager v2 compatibility is being tested.');
    }
}
