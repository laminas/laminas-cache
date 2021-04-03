<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Webmozart\Assert\Assert;

use function is_bool;
use function trim;

/**
 * Storage cache factory for multiple caches.
 *
 * @psalm-import-type StorageAdapterArrayConfigurationType from StorageAdapterFactoryInterface
 * @psalm-type StorageAdapterArrayConfigurationMapType = array<non-empty-string,StorageAdapterArrayConfigurationType>
 */
final class StorageCacheAbstractServiceFactory implements AbstractFactoryInterface
{
    /** @psalm-var StorageAdapterArrayConfigurationMapType|null */
    private $config;

    public const CONFIG_KEY = 'caches';

    /** @var StorageAdapterFactoryInterface|null|bool */
    private $storageAdapterFactory = false;

    /**
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $storageAdapterFactory = $this->getStorageAdapterFactory($container);
        if ($storageAdapterFactory === null) {
            return false;
        }

        if (trim($requestedName) === '') {
            return false;
        }

        $config = $this->getConfig($container, $storageAdapterFactory);

        return isset($config[$requestedName]);
    }

    /**
     * @param string            $requestedName
     * @param null|array<mixed> $options
     * @return StorageInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $adapterFactory = $this->getStorageAdapterFactory($container);
        Assert::isInstanceOf($adapterFactory, StorageAdapterFactoryInterface::class);
        $config = $this->getConfig($container, $adapterFactory);
        Assert::stringNotEmpty($requestedName);
        Assert::keyExists($config, $requestedName);
        return $adapterFactory->createFromArrayConfiguration($config[$requestedName]);
    }

    /**
     * Retrieve cache configuration, if any available.
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-return StorageAdapterArrayConfigurationMapType
     */
    private function getConfig(
        ContainerInterface $container,
        StorageAdapterFactoryInterface $storageAdapterFactory
    ): array {
        if ($this->config !== null) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $container->get('config');
        Assert::isArrayAccessible($config);
        if (! isset($config[self::CONFIG_KEY])) {
            $this->config = [];
            return $this->config;
        }

        $configuration = $config[self::CONFIG_KEY];
        $this->assertConfigurationIsValid($storageAdapterFactory, $configuration);

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->config = $configuration;
        /** @psalm-suppress InvalidReturnStatement */
        return $this->config;
    }

    /**
     * @param mixed $configuration
     * @psalm-assert StorageAdapterArrayConfigurationMapType $configuration
     */
    private function assertConfigurationIsValid(
        StorageAdapterFactoryInterface $storageAdapterFactory,
        $configuration
    ): void {
        Assert::isMap($configuration);
        foreach ($configuration as $cache => $cacheConfiguration) {
            Assert::stringNotEmpty($cache);
            Assert::isMap($cacheConfiguration);
            $storageAdapterFactory->assertValidConfigurationStructure($cacheConfiguration);
        }
    }

    private function getStorageAdapterFactory(ContainerInterface $container): ?StorageAdapterFactoryInterface
    {
        if (! is_bool($this->storageAdapterFactory)) {
            return $this->storageAdapterFactory;
        }

        if (! $container->has(StorageAdapterFactoryInterface::class)) {
            $this->storageAdapterFactory = null;
            return null;
        }

        $storageAdapterFactory       = $container->get(StorageAdapterFactoryInterface::class);
        $this->storageAdapterFactory = $storageAdapterFactory;

        return $storageAdapterFactory;
    }
}
