<?php

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class StorageAdapterPluginManagerFactory implements FactoryInterface
{
    /**
     * laminas-servicemanager v2 support for invocation options.
     *
     * @param array
     */
    protected $creationOptions;

    /**
     * {@inheritDoc}
     *
     * @return AdapterPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new AdapterPluginManager($container, $options ?: []);
    }

    /**
     * {@inheritDoc}
     *
     * @return AdapterPluginManager
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        return $this($container, $requestedName ?: AdapterPluginManager::class, $this->creationOptions);
    }

    /**
     * laminas-servicemanager v2 support for invocation options.
     *
     * @param array $options
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->creationOptions = $options;
    }
}
