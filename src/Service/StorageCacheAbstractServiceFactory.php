<?php

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Webmozart\Assert\Assert;

use function assert;
use function is_array;

/**
 * Storage cache factory for multiple caches.
 */
class StorageCacheAbstractServiceFactory implements AbstractFactoryInterface
{
    public const CACHES_CONFIGURATION_KEY = 'caches';

    /** @var array<string,mixed>|null */
    protected $config;

    /**
     * Configuration key for cache objects
     *
     * @var string
     */
    protected $configKey = self::CACHES_CONFIGURATION_KEY;

    /**
     * @param string $requestedName
     * @return boolean
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $this->getConfig($container);
        if (empty($config)) {
            return false;
        }
        return isset($config[$requestedName]) && is_array($config[$requestedName]);
    }

    /**
     * Create an object
     *
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config  = $this->getConfig($container);
        $factory = $container->get(StorageAdapterFactoryInterface::class);
        assert($factory instanceof StorageAdapterFactoryInterface);
        $configForRequestedName = $config[$requestedName] ?? [];
        Assert::isMap($configForRequestedName);
        $factory->assertValidConfigurationStructure($configForRequestedName);
        return $factory->createFromArrayConfiguration($configForRequestedName);
    }

    /**
     * Retrieve cache configuration, if any
     *
     * @return array
     */
    protected function getConfig(ContainerInterface $container)
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $container->get('config');
        Assert::isArrayAccessible($config);
        if (! isset($config[$this->configKey])) {
            $this->config = [];
            return $this->config;
        }

        $cacheConfigurations = $config[$this->configKey];
        Assert::isMap($cacheConfigurations);

        return $this->config = $cacheConfigurations;
    }
}
