<?php

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

use function is_array;

/**
 * Storage cache factory for multiple caches.
 */
class StorageCacheAbstractServiceFactory implements AbstractFactoryInterface
{
    use PluginManagerLookupTrait;

    public const CACHES_CONFIGURATION_KEY = 'caches';

    /** @var array */
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
        $this->prepareStorageFactory($container);

        $config = $this->getConfig($container);
        return StorageFactory::factory($config[$requestedName]);
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
        if (! isset($config[$this->configKey])) {
            $this->config = [];
            return $this->config;
        }

        $this->config = $config[$this->configKey];
        return $this->config;
    }
}
