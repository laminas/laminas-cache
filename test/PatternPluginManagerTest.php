<?php

namespace LaminasTest\Cache;

use Laminas\Cache\Pattern;
use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use LaminasTest\Cache\Pattern\TestAsset\TestCachePattern;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PatternPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager(): PatternPluginManager
    {
        return new PatternPluginManager(new ServiceManager());
    }

    protected function getInstanceOf(): string
    {
        return PatternInterface::class;
    }

    public function testGetWillInjectProvidedOptionsAsPatternOptionsInstance(): void
    {
        $plugins = $this->getPluginManager();
        $storage = $this->createMock(StorageInterface::class);
        $plugin  = $plugins->get('callback', [
            'cache_output' => false,
            'storage'      => $storage,
        ]);
        $options = $plugin->getOptions();
        self::assertFalse($options->getCacheOutput());
        self::assertSame($storage, $options->getStorage());
    }

    public function testBuildWillInjectProvidedOptionsAsPatternOptionsInstance(): void
    {
        $plugins = $this->getPluginManager();

        $storage = $this->createMock(StorageInterface::class);
        $plugin  = $plugins->build('callback', [
            'cache_output' => false,
            'storage'      => $storage,
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

    public function testHasPatternCacheFactoriesConfigured(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $instance  = new class ($container) extends PatternPluginManager {
            public function getFactories(): array
            {
                return $this->factories;
            }
        };

        self::assertEquals([
            Pattern\CallbackCache::class => Pattern\StoragePatternCacheFactory::class,
            Pattern\CaptureCache::class  => Pattern\PatternCacheFactory::class,
            Pattern\ClassCache::class    => Pattern\StoragePatternCacheFactory::class,
            Pattern\ObjectCache::class   => Pattern\StoragePatternCacheFactory::class,
            Pattern\OutputCache::class   => Pattern\StoragePatternCacheFactory::class,
        ], $instance->getFactories());
    }

    public function testWillPassOptionsToCachePattern(): void
    {
        $this->markTestIncomplete(
            'Will re-enable this when bugfix of https://github.com/laminas/laminas-cache/issues/138 is available.'
        );
        $patternPluginManager = $this->getPluginManager();
        $patternPluginManager->setInvokableClass(TestCachePattern::class);
        $options = ['cache_output' => false];

        $instance = $patternPluginManager->build(TestCachePattern::class, $options);
        self::assertInstanceOf(TestCachePattern::class, $instance);
        self::assertFalse($instance->getOptions()->getCacheOutput());
    }
}
