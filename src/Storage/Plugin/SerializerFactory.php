<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Serializer\AdapterPluginManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function assert;
use function class_exists;

final class SerializerFactory
{
    public function __invoke(ContainerInterface $container): Serializer
    {
        $serializerAdapterPluginManager = $this->getOrCreateAdapterPluginManager($container);

        return new Serializer($serializerAdapterPluginManager);
    }

    private function getOrCreateAdapterPluginManager(ContainerInterface $container): AdapterPluginManager
    {
        if ($container->has(AdapterPluginManager::class)) {
            $pluginManager = $container->get(AdapterPluginManager::class);
            assert($pluginManager instanceof AdapterPluginManager);
            return $pluginManager;
        }

        if (! class_exists(AdapterPluginManager::class)) {
            throw new RuntimeException('Missing `laminas/laminas-serializer` dependency.');
        }

        return new AdapterPluginManager($container);
    }
}
