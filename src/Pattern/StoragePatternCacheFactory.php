<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Cache\StorageFactory;
use Psr\Container\ContainerInterface;
use function class_exists;
use function is_string;

/**
 * @deprecated This will be removed with v3.0.0 as providing generic factories for cache patterns wont be suitable for
 *             all possible combinations of implemented upstream patterns.
 */
final class StoragePatternCacheFactory
{
    /**
     * @var callable(ContainerInterface,array|string):StorageInterface
     */
    private $storageFactory;

    /**
     * @param (callable(ContainerInterface,array|string):StorageInterface)|null $storageFactory
     */
    public function __construct(callable $storageFactory = null)
    {
        $this->storageFactory = $storageFactory ?? static function ($container, $storageOption): StorageInterface {
            if (is_string($storageOption)) {
                $adapterPluginManager = $container->get(AdapterPluginManager::class);
                return $adapterPluginManager->get($storageOption);
            }

            return StorageFactory::factory($storageOption);
        };
    }

    /**
     * @template T of StorageCapableInterface
     * @psalm-param class-string<T>  $requestedName
     * @psalm-return T
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        array $options = null
    ): StorageCapableInterface {
        if (! class_exists($requestedName)) {
            throw new InvalidArgumentException(sprintf(
                'Factory %s is used for a service %s which does not exist.'
                . ' Please only use this factory for services with full qualified class names!',
                self::class,
                $requestedName
            ));
        }

        $storageOption = $options['storage'] ?? null;
        $storageInstance = null;
        if (is_array($storageOption) || is_string($storageOption)) {
            $storageInstance = ($this->storageFactory)($container, $storageOption);
            $options['storage'] = $storageInstance;
        }

        $patternOptions = new PatternOptions($options);
        return new $requestedName($storageInstance, $patternOptions);
    }
}
