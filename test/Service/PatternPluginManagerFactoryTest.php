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
use PHPUnit\Framework\TestCase;

class PatternPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new PatternPluginManagerFactory();

        $patterns = $factory($container, PatternPluginManager::class);
        self::assertInstanceOf(PatternPluginManager::class, $patterns);
    }

    public function testFactoryConfiguresPluginManagerUnderContainerInterop(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $pattern = $this->createMock(PatternInterface::class);

        $factory = new PatternPluginManagerFactory();
        $patterns = $factory($container, PatternPluginManager::class, [
            'services' => [
                'test' => $pattern,
            ],
        ]);
        self::assertSame($pattern, $patterns->get('test'));
    }
}
