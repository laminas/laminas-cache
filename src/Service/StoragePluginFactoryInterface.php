<?php

declare(strict_types=1);

namespace Laminas\Cache\Service;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\Plugin\PluginInterface;

/**
 * @psalm-type PluginArrayConfigurationType = array{name:non-empty-string,options?:array<string,mixed>}
 */
interface StoragePluginFactoryInterface
{
    /**
     * @psalm-param PluginArrayConfigurationType $configuration
     */
    public function createFromArrayConfiguration(array $configuration): PluginInterface;

    /**
     * @template T of PluginInterface
     * @param non-empty-string|class-string<T> $plugin
     * @param array<string,mixed>  $options
     * @return ($plugin is class-string ? T : PluginInterface)
     */
    public function create(string $plugin, array $options = []): PluginInterface;

    /**
     * @param array<mixed> $configuration
     * @psalm-assert PluginArrayConfigurationType $configuration
     * @throws InvalidArgumentException If the provided configuration is invalid.
     */
    public function assertValidConfigurationStructure(array $configuration): void;
}
