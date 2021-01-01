<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Service;

use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Service\PatternPluginManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PatternPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new PatternPluginManagerFactory();

        $patterns = $factory($container);
        self::assertInstanceOf(PatternPluginManager::class, $patterns);
    }
}
