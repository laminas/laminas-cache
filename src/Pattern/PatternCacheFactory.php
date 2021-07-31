<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use function class_exists;

/**
 * @deprecated This will be removed with v3.0.0 as providing generic factories for cache patterns wont be suitable for
 *             all possible combinations of implemented upstream patterns.
 */
final class PatternCacheFactory
{
    /**
     * @template T of PatternInterface
     * @psalm-param class-string<T>             $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        array $options = null
    ): PatternInterface {
        if (! class_exists($requestedName)) {
            throw new InvalidArgumentException(sprintf(
                'Factory %s is used for a service %s which does not exist.'
                . ' Please only use this factory for services with full qualified class names!',
                self::class,
                $requestedName
            ));
        }

        $patternOptions = new PatternOptions($options);
        return new $requestedName($patternOptions);
    }
}
