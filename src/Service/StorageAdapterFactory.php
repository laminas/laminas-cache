<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use InvalidArgumentException;
use Laminas\Cache\Exception;
use Laminas\Cache\Storage\PluginAwareInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use Webmozart\Assert\Assert;

use function assert;
use function get_class;
use function is_string;
use function sprintf;

/**
 * @psalm-import-type PluginArrayConfigurationWithPriorityType from StorageAdapterFactoryInterface
 */
final class StorageAdapterFactory implements StorageAdapterFactoryInterface
{
    public const DEFAULT_PLUGIN_PRIORITY = 1;

    /** @var PluginManagerInterface */
    private $adapters;

    /** @var StoragePluginFactoryInterface */
    private $pluginFactory;

    public function __construct(PluginManagerInterface $adapters, StoragePluginFactoryInterface $pluginFactory)
    {
        $this->adapters      = $adapters;
        $this->pluginFactory = $pluginFactory;
    }

    public function createFromArrayConfiguration(array $configuration): StorageInterface
    {
        $adapterName = $configuration['adapter'] ?? $configuration['name'] ?? null;
        Assert::stringNotEmpty($adapterName, 'Configuration must contain a "adapter" key.');
        $adapterOptions = $configuration['options'] ?? [];
        $plugins        = $configuration['plugins'] ?? [];

        return $this->create($adapterName, $adapterOptions, $plugins);
    }

    public function create(string $storage, array $options = [], array $plugins = []): StorageInterface
    {
        $adapter = $this->adapters->build($storage, $options);
        assert($adapter instanceof StorageInterface);

        if ($plugins === []) {
            return $adapter;
        }

        if (! $adapter instanceof PluginAwareInterface) {
            throw new Exception\RuntimeException(sprintf(
                "The adapter '%s' doesn't implement '%s' and therefore can't handle plugins",
                get_class($adapter),
                PluginAwareInterface::class
            ));
        }

        foreach ($plugins as $pluginConfiguration) {
            $plugin         = $this->pluginFactory->createFromArrayConfiguration($pluginConfiguration);
            $pluginPriority = $pluginConfiguration['priority'] ?? self::DEFAULT_PLUGIN_PRIORITY;

            if (! $adapter->hasPlugin($plugin)) {
                $adapter->addPlugin($plugin, $pluginPriority);
            }
        }

        return $adapter;
    }

    public function assertValidConfigurationStructure(array $configuration): void
    {
        try {
            Assert::isNonEmptyMap($configuration, 'Configuration must be a non-empty array.');

            $adapter = $configuration['adapter'] ?? $configuration['name'] ?? null;

            if (! is_string($adapter)) {
                throw new InvalidArgumentException('Configuration must contain a "adapter" key.');
            }

            Assert::stringNotEmpty($adapter, 'Storage "adapter" has to be a non-empty string.');
            Assert::nullOrIsMap(
                $configuration['options'] ?? null,
                'Storage "options" must be an array with string keys.'
            );
            if (isset($configuration['plugins'])) {
                Assert::isList($configuration['plugins'], 'Storage "plugins" must be a list of plugin configurations.');
                $this->assertValidPluginConfigurationStructure($adapter, $configuration['plugins']);
            }
        } catch (InvalidArgumentException $exception) {
            throw new Exception\InvalidArgumentException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @psalm-param non-empty-string $adapter
     * @psalm-param list<mixed> $plugins
     * @psalm-assert list<PluginArrayConfigurationWithPriorityType> $plugins
     */
    private function assertValidPluginConfigurationStructure(string $adapter, array $plugins): void
    {
        Assert::allIsArray($plugins, 'All plugin configurations are expected to be an array.');
        foreach ($plugins as $pluginConfiguration) {
            try {
                $this->pluginFactory->assertValidConfigurationStructure($pluginConfiguration);
                if (isset($pluginConfiguration['priority'])) {
                    Assert::integer($pluginConfiguration['priority'], 'Plugin priority has to be integer.');
                }
            } catch (Exception\InvalidArgumentException | InvalidArgumentException $exception) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'Plugin configuration for adapter "%s" is invalid: %s',
                        $adapter,
                        $exception->getMessage()
                    ),
                    0,
                    $exception
                );
            }
        }
    }
}
