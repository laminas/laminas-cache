<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;
use Laminas\Cache\Storage\StorageInterface;
use Stringable;
use Throwable;

use function array_shift;
use function array_unshift;
use function assert;
use function func_get_args;
use function in_array;
use function method_exists;
use function property_exists;
use function sprintf;
use function strtolower;

class ObjectCache extends AbstractStorageCapablePattern implements Stringable
{
    private CallbackCache $callbackCache;

    public function __construct(StorageInterface $storage, ?PatternOptions $options = null)
    {
        parent::__construct($storage, $options);
        $this->callbackCache = new CallbackCache($storage, $options);
        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    public function setOptions(PatternOptions $options): self
    {
        parent::setOptions($options);
        $this->callbackCache->setOptions($options);

        if (! $options->getObject()) {
            throw new Exception\InvalidArgumentException("Missing option 'object'");
        }

        return $this;
    }

    /**
     * Call and cache a class method
     *
     * @param  non-empty-string $method  Method name to call
     * @param  array  $args    Method arguments
     * @throws Exception\RuntimeException
     * @throws Throwable
     */
    public function call(string $method, array $args = []): mixed
    {
        $options = $this->getOptions();
        $object  = $options->getObject();
        assert($object !== null, 'ObjectCache#setOptions verifies that we always have an object set.');

        $method = strtolower($method);

        // handle magic methods
        switch ($method) {
            case '__set':
                $property = array_shift($args);
                $value    = array_shift($args);

                $object->{$property} = $value;

                if (
                    ! $options->getObjectCacheMagicProperties()
                    || property_exists($object, $property)
                ) {
                    // no caching if property isn't magic
                    // or caching magic properties is disabled
                    return null;
                }

                // remove cached __get and __isset
                $removeKeys = [];
                if (method_exists($object, '__get')) {
                    $removeKeys[] = $this->generateKey('__get', [$property]);
                }
                if (method_exists($object, '__isset')) {
                    $removeKeys[] = $this->generateKey('__isset', [$property]);
                }
                if ($removeKeys !== []) {
                    $storage = $this->getStorage();
                    $storage->removeItems($removeKeys);
                }
                return null;

            case '__get':
                $property = array_shift($args);

                if (
                    ! $options->getObjectCacheMagicProperties()
                    || property_exists($object, $property)
                ) {
                    // no caching if property isn't magic
                    // or caching magic properties is disabled
                    return $object->{$property};
                }

                array_unshift($args, $property);
                return $this->callbackCache->call([$object, '__get'], $args);

            case '__isset':
                $property = array_shift($args);

                if (
                    ! $options->getObjectCacheMagicProperties()
                    || property_exists($object, $property)
                ) {
                    // no caching if property isn't magic
                    // or caching magic properties is disabled
                    return isset($object->{$property});
                }

                return $this->callbackCache->call([$object, '__isset'], [$property]);

            case '__unset':
                $property = array_shift($args);

                unset($object->{$property});

                if (
                    ! $options->getObjectCacheMagicProperties()
                    || property_exists($object, $property)
                ) {
                    // no caching if property isn't magic
                    // or caching magic properties is disabled
                    return null;
                }

                // remove previous cached __get and __isset calls
                $removeKeys = [];
                if (method_exists($object, '__get')) {
                    $removeKeys[] = $this->generateKey('__get', [$property]);
                }
                if (method_exists($object, '__isset')) {
                    $removeKeys[] = $this->generateKey('__isset', [$property]);
                }
                if ($removeKeys !== []) {
                    $storage = $this->getStorage();
                    $storage->removeItems($removeKeys);
                }
                return null;
        }

        if (! method_exists($object, $method)) {
            throw new Exception\RuntimeException(sprintf(
                '%s only accepts methods which are implemented by %s',
                $this::class,
                $object::class,
            ));
        }

        $cache = $options->getCacheByDefault();
        if ($cache) {
            $cache = ! in_array($method, $options->getObjectNonCacheMethods());
        } else {
            $cache = in_array($method, $options->getObjectCacheMethods());
        }

        if (! $cache) {
            return $object->{$method}(...$args);
        }

        return $this->callbackCache->call([$object, $method], $args);
    }

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  non-empty-string $methodOrProperty The method or the property
     * @param  array            $args             Callback arguments
     * @return non-empty-string
     * @throws Exception\RuntimeException
     */
    public function generateKey(string $methodOrProperty, array $args = []): string
    {
        $object = $this->getOptions()->getObject();
        assert($object !== null, 'ObjectCache#setOptions verifies that we always have an object set.');

        return $this->callbackCache->generateKey([$object, $methodOrProperty], $args);
    }

    /**
     * Class method call handler
     *
     * @param  non-empty-string $method  Method name to call
     * @param  array  $args    Method arguments
     * @throws Exception\RuntimeException
     * @throws Throwable
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->call($method, $args);
    }

    /**
     * Writing data to properties.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __set
     * and removes cached data of previous __get and __isset calls.
     *
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     *
     * @param  non-empty-string $name
     */
    public function __set($name, mixed $value): void
    {
        $this->call('__set', [$name, $value]);
    }

    /**
     * Reading data from properties.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __get.
     *
     * @see http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     *
     * @param non-empty-string $name
     */
    public function __get(string $name): mixed
    {
        return $this->call('__get', [$name]);
    }

    /**
     * Checking existing properties.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __get.
     *
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     *
     * @param  non-empty-string $name
     */
    public function __isset(string $name): bool
    {
        return $this->call('__isset', [$name]);
    }

    /**
     * Unseting a property.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it removes
     * previous cached __isset and __get calls.
     *
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     *
     * @param  non-empty-string $name
     */
    public function __unset(string $name): void
    {
        $this->call('__unset', [$name]);
    }

    /**
     * Handle casting to string
     *
     * @see    http://php.net/manual/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString(): string
    {
        return (string) $this->call('__toString');
    }

    /**
     * Handle invoke calls
     *
     * @see    http://php.net/manual/language.oop5.magic.php#language.oop5.magic.invoke
     */
    public function __invoke(): mixed
    {
        return $this->call('__invoke', func_get_args());
    }
}
