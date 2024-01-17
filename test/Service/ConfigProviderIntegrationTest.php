<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Generator;
use InvalidArgumentException;
use Laminas\Cache\ConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_keys;
use function array_merge;
use function array_unique;
use function is_array;
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
     * @dataProvider servicesProvidedByConfigProvider
     */
    public function testContainerCanProvideRegisteredServices(string $serviceName): void
    {
        $instance = $this->container->get($serviceName);
        self::assertIsObject($instance);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public function servicesProvidedByConfigProvider(): Generator
    {
        $provider     = new ConfigProvider();
        $dependencies = $provider->getDependencyConfig();

        $factories = $dependencies['factories'] ?? [];
        self::assertArrayIsMappedWithStrings($factories);
        $invokables = $dependencies['invokables'] ?? [];
        self::assertArrayIsMappedWithStrings($invokables);
        $services = $dependencies['services'] ?? [];
        self::assertArrayIsMappedWithStrings($services);
        $aliases = $dependencies['aliases'] ?? [];
        self::assertArrayIsMappedWithStrings($aliases);

        $serviceNames = array_unique(
            array_merge(
                array_keys($factories),
                array_keys($invokables),
                array_keys($services),
                array_keys($aliases),
            ),
        );

        foreach ($serviceNames as $serviceName) {
            yield $serviceName => [$serviceName];
        }
    }

    /**
     * @psalm-assert array<string,mixed> $array
     */
    private static function assertArrayIsMappedWithStrings(mixed $array): void
    {
        if (! is_array($array)) {
            throw new InvalidArgumentException('Expecting value to be an array.');
        }

        foreach (array_keys($array) as $value) {
            if (is_string($value)) {
                continue;
            }

            throw new InvalidArgumentException('Expecting all values to are mapped with a string.');
        }
    }
}
