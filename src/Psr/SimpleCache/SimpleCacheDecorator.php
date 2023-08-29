<?php

namespace Laminas\Cache\Psr\SimpleCache;

use DateInterval;
use DateTimeZone;
use Laminas\Cache\Exception\InvalidArgumentException as LaminasCacheInvalidArgumentException;
use Laminas\Cache\Psr\Clock;
use Laminas\Cache\Psr\MaximumKeyLengthTrait;
use Laminas\Cache\Psr\SerializationTrait;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheException as PsrCacheException;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Throwable;

use function array_keys;
use function array_map;
use function get_debug_type;
use function gettype;
use function is_array;
use function is_int;
use function is_iterable;
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

    private bool $providesPerItemTtl = true;

    /**
     * Reference used by storage when calling getItem() to indicate status of
     * operation.
     */
    private ?bool $success = null;

    private ClockInterface $clock;

    public function __construct(
        private readonly StorageInterface $storage,
        ?ClockInterface $clock = null,
    ) {
        if ($this->isSerializationRequired($storage)) {
            throw new SimpleCacheException(sprintf(
                'The storage adapter "%s" requires a serializer plugin; please see'
                . ' https://docs.laminas.dev/laminas-cache/storage/plugin/#quick-start'
                . ' for details on how to attach the plugin to your adapter.',
                $storage::class
            ));
        }

        $capabilities = $storage->getCapabilities();
        $this->memoizeTtlCapabilities($capabilities);
        $this->memoizeMaximumKeyLengthCapability($storage, $capabilities);
        $this->clock = $clock ?? new Clock(new DateTimeZone('UTC'));
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $this->success = null;
        try {
            $result = $this->storage->getItem($key, $this->success);
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }

        $result ??= $default;
        return $this->success ? $result : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
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
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        try {
            return null !== $this->storage->removeItem($key);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
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
    public function getMultiple(iterable $keys, mixed $default = null): array
    {
        if (! is_array($keys) && ! is_iterable($keys)) {
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
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        if (! is_array($values) && ! is_iterable($values)) {
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
            return $this->deleteMultiple(array_map(fn (int|string $key) => (string) $key, $keys));
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
    public function deleteMultiple(iterable $keys): bool
    {
        if (! is_array($keys) && ! is_iterable($keys)) {
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
        } catch (Throwable) {
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
    public function has(string $key): bool
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
     * @throws SimpleCacheInvalidArgumentException If key is invalid.
     */
    private function validateKey(string|int $key): void
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
     * @throws SimpleCacheInvalidArgumentException For invalid arguments.
     */
    private function convertTtlToInteger(int|DateInterval|null $ttl): int|null
    {
        // null === absence of a TTL
        if (null === $ttl) {
            return null;
        }

        // integers are always okay
        if (is_int($ttl)) {
            return $ttl;
        }

        $now = $this->clock->now();
        $end = $now->add($ttl);
        return $end->getTimestamp() - $now->getTimestamp();
    }

    /**
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
                    get_debug_type($key)
                ));
            }

            $this->validateKey($key);
            $array[] = $key;
        }

        return $array;
    }

    /**
     * @return array<int|string,mixed>
     */
    private function convertIterableToKeyValueMap(iterable $values): array
    {
        $keyValueMap = [];
        foreach ($values as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                throw new SimpleCacheInvalidArgumentException(sprintf(
                    'Invalid key detected of type "%s"; must be a scalar',
                    get_debug_type($key)
                ));
            }

            $this->validateKey($key);

            $keyValueMap[$key] = $value;
        }

        return $keyValueMap;
    }
}
