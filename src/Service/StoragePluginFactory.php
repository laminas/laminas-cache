<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use InvalidArgumentException as PhpInvalidArgumentException;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use Webmozart\Assert\Assert;

use function assert;

final class StoragePluginFactory implements StoragePluginFactoryInterface
{
    /** @var PluginManagerInterface */
    private $plugins;

    public function __construct(PluginManagerInterface $plugins)
    {
        $this->plugins = $plugins;
    }

    public function createFromArrayConfiguration(array $configuration): PluginInterface
    {
        $name = $configuration['adapter'] ?? $configuration['name'] ?? null;
        Assert::stringNotEmpty($name, 'Configuration must contain a "adapter" key.');
        $options = $configuration['options'] ?? [];

        return $this->create($name, $options);
    }

    public function create(string $plugin, array $options = []): PluginInterface
    {
        $instance = $this->plugins->build($plugin, $options);
        assert($instance instanceof PluginInterface);
        return $instance;
    }

    public function assertValidConfigurationStructure(array $configuration): void
    {
        try {
            Assert::isNonEmptyMap($configuration, 'Configuration must be a non-empty array.');
            if (! isset($configuration['name']) && ! isset($configuration['adapter'])) {
                throw new PhpInvalidArgumentException('Configuration must contain a "adapter" key.');
            }

            Assert::stringNotEmpty(
                $configuration['adapter'] ?? $configuration['name'],
                'Plugin "adapter" has to be a non-empty string.'
            );

            Assert::nullOrIsMap(
                $configuration['options'] ?? null,
                'Plugin "options" must be an array with string keys.'
            );
        } catch (PhpInvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }
    }
}
