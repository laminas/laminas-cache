<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StoragePluginFactoryFactory;
use Laminas\Cache\Storage\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StoragePluginFactoryFactoryTest extends TestCase
{
    /** @var StoragePluginFactoryFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new StoragePluginFactoryFactory();
    }

    public function testWillRetrieveDependenciesFromContainer(): void
    {
        $plugins   = $this->createMock(PluginManager::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('get')
            ->with(PluginManager::class)
            ->willReturn($plugins);

        ($this->factory)($container);
    }
}
