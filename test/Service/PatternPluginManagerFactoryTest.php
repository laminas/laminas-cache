<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Service\PatternPluginManagerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit_Framework_TestCase as TestCase;

class PatternPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new PatternPluginManagerFactory();

        $patterns = $factory($container, PatternPluginManager::class);
        $this->assertInstanceOf(PatternPluginManager::class, $patterns);

        if (method_exists($patterns, 'configure')) {
            // laminas-servicemanager v3
            $this->assertAttributeSame($container, 'creationContext', $patterns);
        } else {
            // laminas-servicemanager v2
            $this->assertSame($container, $patterns->getServiceLocator());
        }
    }

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderContainerInterop()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $pattern = $this->prophesize(PatternInterface::class)->reveal();

        $factory = new PatternPluginManagerFactory();
        $patterns = $factory($container, PatternPluginManager::class, [
            'services' => [
                'test' => $pattern,
            ],
        ]);
        $this->assertSame($pattern, $patterns->get('test'));
    }

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderServiceManagerV2()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $pattern = $this->prophesize(PatternInterface::class)->reveal();

        $factory = new PatternPluginManagerFactory();
        $factory->setCreationOptions([
            'services' => [
                'test' => $pattern,
            ],
        ]);

        $patterns = $factory->createService($container->reveal());
        $this->assertSame($pattern, $patterns->get('test'));
    }
}
