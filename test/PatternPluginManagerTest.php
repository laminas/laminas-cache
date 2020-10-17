<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache;

use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PatternPluginManagerTest extends TestCase
{
    use ProphecyTrait;

    use CommonPluginManagerTrait;

    protected function getPluginManager()
    {
        return new PatternPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return PatternInterface::class;
    }

    public function testGetWillInjectProvidedOptionsAsPatternOptionsInstance()
    {
        $plugins = $this->getPluginManager();
        $storage = $this->prophesize(StorageInterface::class)->reveal();
        $plugin = $plugins->get('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        $this->assertFalse($options->getCacheOutput());
        $this->assertSame($storage, $options->getStorage());
    }

    public function testBuildWillInjectProvidedOptionsAsPatternOptionsInstance()
    {
        $plugins = $this->getPluginManager();

        if (! method_exists($plugins, 'configure')) {
            $this->markTestSkipped('Test is only relevant for laminas-servicemanager v3');
        }

        $storage = $this->prophesize(StorageInterface::class)->reveal();
        $plugin = $plugins->build('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        $this->assertFalse($options->getCacheOutput());
        $this->assertSame($storage, $options->getStorage());
    }
}
