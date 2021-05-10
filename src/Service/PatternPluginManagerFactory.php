<?php

namespace Laminas\Cache\Service;

use Interop\Container\ContainerInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class PatternPluginManagerFactory implements FactoryInterface
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
     * @return PatternPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new PatternPluginManager($container, $options ?: []);
    }

    /**
     * {@inheritDoc}
     *
     * @return PatternPluginManager
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        return $this($container, $requestedName ?: PatternPluginManager::class, $this->creationOptions);
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
