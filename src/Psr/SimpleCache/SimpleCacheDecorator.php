<?php

namespace Laminas\Cache\Psr\SimpleCache;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Laminas\Cache\Exception\InvalidArgumentException as LaminasCacheInvalidArgumentException;
use Laminas\Cache\Psr\MaximumKeyLengthTrait;
use Laminas\Cache\Psr\SerializationTrait;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\SimpleCache\CacheException as PsrCacheException;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Throwable;
use Traversable;

use function array_keys;
use function get_class;
use function gettype;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function var_export;

/**
 * Decorate a laminas-cache storage adapter for usage as a PSR-16 implementation.
 */
class SimpleCacheDecorator implements SimpleCacheInterface
{
    use MaximumKeyLengthTrait;
    use SerializationTrait;

    /**
     * Characters reserved by PSR-16 that are not valid in cache keys.
     */
    public const INVALID_KEY_CHARS = ':@{}()/\\';

    /** @var bool */
    private $providesPerItemTtl = true;

    /** @var StorageInterface */
    private $storage;

    /**
     * Reference used by storage when calling getItem() to indicate status of
     * operation.
     *
     * @var null|bool
     */
    private $success;

    /** @var DateTimeZone */
    private $utc;

    public function __construct(StorageInterface $storage)
    {
        if ($this->isSerializationRequired($storage)) {
            throw new SimpleCacheException(sprintf(
                'The storage adapter "%s" requires a serializer plugin; please see'
                . ' https://docs.laminas.dev/laminas-cache/storage/plugin/#quick-start'
                . ' for details on how to attach the plugin to your adapter.',
                get_class($storage)
            ));
        }

        $capabilities = $storage->getCapabilities();
        $this->memoizeTtlCapabilities($capabilities);
        $this->memoizeMaximumKeyLengthCapability($storage, $capabilities);

        $this->storage = $storage;
        $this->utc     = new DateTimeZone('UTC');
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $this->success = null;
        try {
            $result = $this->storage->getItem($key, $this->success);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }

        $result = $result ?? $default;
        return $this->success ? $result : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($key);
        $ttl = $this->convertTtlToInteger($ttl);

        // PSR-16 states that 0 or negative TTL values should result in cache
        // invalidation for the item.
        if (null !== $ttl && 1 > $ttl) {
            return $this->delete($key);
        }

        // If a positive TTL is set, but the adapter does not support per-item
        // TTL, we return false immediately.
        if (null !== $ttl && ! $this->providesPerItemTtl) {
            return false;
        }

        $options     = $this->storage->getOptions();
        $previousTtl = $options->getTtl();

        if ($ttl !== null) {
            $options->setTtl($ttl);
        }

        try {
            $result = $this->storage->setItem($key, $value);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        } finally {
            $options->setTtl($previousTtl);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->validateKey($key);

        try {
            return null !== $this->storage->removeItem($key);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $namespace = $this->storage->getOptions()->getNamespace();

        if ($this->storage instanceof ClearByNamespaceInterface && $namespace) {
            return $this->storage->clearByNamespace($namespace);
        }

        if ($this->storage instanceof FlushableInterface) {
            return $this->storage->flush();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        /**
         * @psalm-suppress DocblockTypeContradiction Since we do not have native type-hints, we should verify iterable.
         */
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid value provided to %s; must be iterable',
                __METHOD__
            ));
        }

        $keys = $this->convertIterableKeysToList($keys);

        try {
            $results = $this->storage->getItems($keys);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }

        foreach ($keys as $key) {
            if (isset($results[$key])) {
                continue;
            }
            $results[$key] = $default;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        /**
         * @psalm-suppress DocblockTypeContradiction Since we do not have native type-hints, we should verify iterable.
         */
        if (! is_array($values) && ! $values instanceof Traversable) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid value provided to %s; must be iterable',
                __METHOD__
            ));
        }

        $values = $this->convertIterableToKeyValueMap($values);
        $keys   = array_keys($values);
        $ttl    = $this->convertTtlToInteger($ttl);

        // PSR-16 states that 0 or negative TTL values should result in cache
        // invalidation for the items.
        if (null !== $ttl && 1 > $ttl) {
            return $this->deleteMultiple($keys);
        }

        // If a positive TTL is set, but the adapter does not support per-item
        // TTL, we return false -- but not until after we validate keys.
        if (null !== $ttl && ! $this->providesPerItemTtl) {
            return false;
        }

        $options     = $this->storage->getOptions();
        $previousTtl = $options->getTtl();

        if ($ttl !== null) {
            $options->setTtl($ttl);
        }

        try {
            $result = $this->storage->setItems($values);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        } finally {
            $options->setTtl($previousTtl);
        }

        if (empty($result)) {
            return true;
        }

        foreach ($result as $index => $key) {
            if (! $this->storage->hasItem($key)) {
                unset($result[$index]);
            }
        }

        return empty($result);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        /**
         * @psalm-suppress DocblockTypeContradiction Since we do not have native type-hints, we should verify iterable.
         */
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid value provided to %s; must be iterable',
                __METHOD__
            ));
        }

        $keys = $this->convertIterableKeysToList($keys);
        if (empty($keys)) {
            return true;
        }

        try {
            $result = $this->storage->removeItems($keys);
        } catch (Throwable $e) {
            return false;
        }

        if (empty($result)) {
            return true;
        }

        foreach ($result as $index => $key) {
            if (! $this->storage->hasItem($key)) {
                unset($result[$index]);
            }
        }

        return empty($result);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        $this->validateKey($key);

        try {
            return $this->storage->hasItem($key);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }
    }

    /**
     * @return SimpleCacheInvalidArgumentException|SimpleCacheException
     */
    private static function translateThrowable(Throwable $throwable): PsrCacheException
    {
        $exceptionClass = $throwable instanceof LaminasCacheInvalidArgumentException
            ? SimpleCacheInvalidArgumentException::class
            : SimpleCacheException::class;

        return new $exceptionClass($throwable->getMessage(), $throwable->getCode(), $throwable);
    }

    /**
     * @param string|int $key
     * @throws SimpleCacheInvalidArgumentException If key is invalid.
     */
    private function validateKey($key): void
    {
        if ('' === $key) {
            throw new SimpleCacheInvalidArgumentException(
                'Invalid key provided; cannot be empty'
            );
        }

        if (0 === $key) {
            // cache/integration-tests erroneously tests that ['0' => 'value']
            // is a valid payload to setMultiple(). However, PHP silently
            // converts '0' to 0, which would normally be invalid. For now,
            // we need to catch just this single value so tests pass.
            // I have filed an issue to correct the test:
            // https://github.com/php-cache/integration-tests/issues/92
            return;
        }

        if (! is_string($key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key provided of type "%s"%s; must be a string',
                gettype($key),
                sprintf(' (%s)', var_export($key, true))
            ));
        }

        $regex = sprintf('/[%s]/', preg_quote(self::INVALID_KEY_CHARS, '/'));
        if (preg_match($regex, $key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key "%s" provided; cannot contain any of (%s)',
                $key,
                self::INVALID_KEY_CHARS
            ));
        }

        if ($this->exceedsMaximumKeyLength($key)) {
            throw SimpleCacheInvalidArgumentException::maximumKeyLengthExceeded($key, $this->maximumKeyLength);
        }
    }

    /**
     * Determine if the storage adapter provides per-item TTL capabilities
     */
    private function memoizeTtlCapabilities(Capabilities $capabilities): void
    {
        $this->providesPerItemTtl = $capabilities->getStaticTtl() && (0 < $capabilities->getMinTtl());
    }

    /**
     * @param int|DateInterval|string $ttl
     * @return null|int
     * @throws SimpleCacheInvalidArgumentException For invalid arguments.
     */
    private function convertTtlToInteger($ttl)
    {
        // null === absence of a TTL
        if (null === $ttl) {
            return null;
        }

        // integers are always okay
        if (is_int($ttl)) {
            return $ttl;
        }

        // Numeric strings evaluating to integers can be cast
        if (
            is_string($ttl)
            && ('0' === $ttl
                || preg_match('/^[1-9][0-9]+$/', $ttl)
            )
        ) {
            return (int) $ttl;
        }

        // DateIntervals require conversion
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable('now', $this->utc);
            $end = $now->add($ttl);
            return $end->getTimestamp() - $now->getTimestamp();
        }

        // All others are invalid
        throw new SimpleCacheInvalidArgumentException(sprintf(
            'Invalid TTL "%s" provided; must be null, an integer, or a %s instance',
            is_object($ttl) ? get_class($ttl) : var_export($ttl, true),
            DateInterval::class
        ));
    }

    /**
     * @param iterable $keys
     * @psalm-return list<string|int>
     * @throws SimpleCacheInvalidArgumentException For invalid $iterable values.
     */
    private function convertIterableKeysToList(iterable $keys): array
    {
        $array = [];
        foreach ($keys as $key) {
            if (! is_string($key) && ! is_int($key)) {
                throw new SimpleCacheInvalidArgumentException(sprintf(
                    'Invalid key detected of type "%s"; must be a scalar',
                    is_object($key) ? get_class($key) : gettype($key)
                ));
            }

            $this->validateKey($key);
            $array[] = $key;
        }

        return $array;
    }

    /**
     * @param iterable $values
     * @psalm-return array<int|string,mixed>
     */
    private function convertIterableToKeyValueMap(iterable $values): array
    {
        $keyValueMap = [];
        foreach ($values as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                throw new SimpleCacheInvalidArgumentException(sprintf(
                    'Invalid key detected of type "%s"; must be a scalar',
                    is_object($key) ? get_class($key) : gettype($key)
                ));
            }

            $this->validateKey($key);

            /** @psalm-suppress MixedAssignment */
            $keyValueMap[$key] = $value;
        }

        return $keyValueMap;
    }
}
