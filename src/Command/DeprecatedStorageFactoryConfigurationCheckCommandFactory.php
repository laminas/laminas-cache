<?php

declare(strict_types=1);

namespace Laminas\Cache\Command;

use ArrayAccess;
use ArrayObject;
use Laminas\Cache\Service\DeprecatedSchemaDetector;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;

/**
 * @internal
 */
final class DeprecatedStorageFactoryConfigurationCheckCommandFactory
{
    public function __invoke(ContainerInterface $container): DeprecatedStorageFactoryConfigurationCheckCommand
    {
        $config = $this->detectConfigFromContainer($container);

        $schemaDetector = new DeprecatedSchemaDetector();
        return new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $schemaDetector
        );
    }

    private function detectConfigFromContainer(ContainerInterface $container): ArrayAccess
    {
        if (! $container->has('config')) {
            return new ArrayObject([]);
        }

        $config = $container->get('config');
        if (is_array($config)) {
            $config = new ArrayObject($config);
        }

        if (! $config instanceof ArrayAccess) {
            throw new RuntimeException('Configuration from container must be either `ArrayAccess` or an array.');
        }

        return $config;
    }
}
