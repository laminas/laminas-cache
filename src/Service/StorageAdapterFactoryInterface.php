<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;

/**
 * @psalm-type InternalOptionalPriorityConfigurationType = array{priority?:int}
 * NOTE: have to re-declare this here until https://github.com/vimeo/psalm/issues/5148 is fixed.
 * @psalm-type InternalPluginArrayConfigurationType = array{name:non-empty-string,options?:array<string,mixed>}
 * @psalm-type PluginArrayConfigurationWithPriorityType =
 *              InternalPluginArrayConfigurationType&InternalOptionalPriorityConfigurationType
 * @psalm-type StorageAdapterArrayConfigurationType = array{
 *     name:non-empty-string,
 *     options?:array<string,mixed>,
 *     plugins?: list<PluginArrayConfigurationWithPriorityType>
 * }|array{
 *     adapter:non-empty-string,
 *     options?:array<string,mixed>,
 *     plugins?: list<PluginArrayConfigurationWithPriorityType>
 * }
 */
interface StorageAdapterFactoryInterface
{
    /**
     * @psalm-param StorageAdapterArrayConfigurationType $configuration
     */
    public function createFromArrayConfiguration(array $configuration): StorageInterface;

    /**
     * @psalm-param non-empty-string $storage
     * @param array<string,mixed>  $options
     * @psalm-param list<PluginArrayConfigurationWithPriorityType> $plugins
     */
    public function create(string $storage, array $options = [], array $plugins = []): StorageInterface;

    /**
     * @param array<mixed> $configuration
     * @psalm-assert StorageAdapterArrayConfigurationType $configuration
     * @throws InvalidArgumentException If the provided configuration is invalid.
     */
    public function assertValidConfigurationStructure(array $configuration): void;
}
