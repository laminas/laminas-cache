<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\ConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_keys;
use function array_merge;
use function array_unique;
use function assert;
use function is_string;

final class ConfigProviderIntegrationTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer();
    }

    private function createContainer(): ContainerInterface
    {
        return new ServiceManager((new ConfigProvider())->getDependencyConfig());
    }

    /**
     * @param non-empty-string $serviceName
     * @dataProvider servicesProvidedByConfigProvider
     */
    public function testContainerCanProvideRegisteredServices(string $serviceName): void
    {
        $instance = $this->container->get($serviceName);
        self::assertIsObject($instance);
    }

    /**
     * @return iterable<non-empty-string,array{non-empty-string}>
     */
    public function servicesProvidedByConfigProvider(): iterable
    {
        $provider     = new ConfigProvider();
        $dependencies = $provider->getDependencyConfig();

        $serviceNames = array_unique(
            array_merge(
                array_keys($dependencies['factories'] ?? []),
                array_keys($dependencies['invokables'] ?? []),
                array_keys($dependencies['services'] ?? []),
                array_keys($dependencies['aliases'] ?? []),
            ),
        );

        foreach ($serviceNames as $serviceName) {
            assert(is_string($serviceName) && $serviceName !== '');
            yield $serviceName => [$serviceName];
        }
    }
}
