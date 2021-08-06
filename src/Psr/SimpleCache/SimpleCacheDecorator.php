<?php

namespace Laminas\Cache\Psr\SimpleCache;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Laminas\Cache\Exception\InvalidArgumentException as LaminasCacheInvalidArgumentException;
use Laminas\Cache\Psr\SerializationTrait;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Throwable;
use Traversable;
use function get_class;
use function sprintf;

/**
 * Decorate a laminas-cache storage adapter for usage as a PSR-16 implementation.
 */
class SimpleCacheDecorator implements SimpleCacheInterface
{
    use SerializationTrait;

    /**
     * Characters reserved by PSR-16 that are not valid in cache keys.
     */
    const INVALID_KEY_CHARS = ':@{}()/\\';

    /**
     * PCRE runs into a compilation error if the quantifier exceeds this limit
     * @internal
     */
    public const PCRE_MAXIMUM_QUANTIFIER_LENGTH = 65535;

    /**
     * @var bool
     */
    private $providesPerItemTtl = true;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * Reference used by storage when calling getItem() to indicate status of
     * operation.
     *
     * @var null|bool
     */
    private $success;

    /**
     * @var DateTimeZone
     */
    private $utc;

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    private $maximumKeyLength;

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
        $this->utc = new DateTimeZone('UTC');
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
        } catch (Throwable $throwable) {
            throw static::translateThrowable($throwable);
        }

        $result = $result === null ? $default : $result;
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

        $options = $this->storage->getOptions();
        $previousTtl = $options->getTtl();

        if ($ttl !== null) {
            $options->setTtl($ttl);
        }

        try {
            $result = $this->storage->setItem($key, $value);
        } catch (Throwable $throwable) {
            throw static::translateThrowable($throwable);
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
        } catch (Throwable $throwable) {
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
        $keys = $this->convertIterableToArray($keys, false, __FUNCTION__);
        array_walk($keys, [$this, 'validateKey']);

        try {
            $results = $this->storage->getItems($keys);
        } catch (Throwable $throwable) {
            throw static::translateThrowable($throwable);
        }

        foreach ($keys as $key) {
            if (! isset($results[$key])) {
                $results[$key] = $default;
                continue;
            }
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $values = $this->convertIterableToArray($values, true, __FUNCTION__);
        $keys = array_keys($values);
        $ttl = $this->convertTtlToInteger($ttl);

        // PSR-16 states that 0 or negative TTL values should result in cache
        // invalidation for the items.
        if (null !== $ttl && 1 > $ttl) {
            return $this->deleteMultiple($keys);
        }

        array_walk($keys, [$this, 'validateKey']);

        // If a positive TTL is set, but the adapter does not support per-item
        // TTL, we return false -- but not until after we validate keys.
        if (null !== $ttl && ! $this->providesPerItemTtl) {
            return false;
        }

        $options = $this->storage->getOptions();
        $previousTtl = $options->getTtl();

        if ($ttl !== null) {
            $options->setTtl($ttl);
        }

        try {
            $result = $this->storage->setItems($values);
        } catch (Throwable $throwable) {
            throw static::translateThrowable($throwable);
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
        $keys = $this->convertIterableToArray($keys, false, __FUNCTION__);
        if (empty($keys)) {
            return true;
        }

        array_walk($keys, [$this, 'validateKey']);

        try {
            $result = $this->storage->removeItems($keys);
        } catch (Throwable $throwable) {
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
        } catch (Throwable $throwable) {
            throw static::translateThrowable($throwable);
        }
    }

    private static function translateThrowable(Throwable $throwable): SimpleCacheException
    {
        $exceptionClass = $throwable instanceof LaminasCacheInvalidArgumentException
            ? SimpleCacheInvalidArgumentException::class
            : SimpleCacheException::class;

        return new $exceptionClass($throwable->getMessage(), $throwable->getCode(), $throwable);
    }

    /**
     * @param string $key
     * @return void
     * @throws SimpleCacheInvalidArgumentException if key is invalid
     */
    private function validateKey($key)
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
            return $key;
        }

        if (! is_string($key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key provided of type "%s"%s; must be a string',
                is_object($key) ? get_class($key) : gettype($key),
                is_scalar($key) ? sprintf(' (%s)', var_export($key, true)) : ''
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

        if ($this->maximumKeyLength !== Capabilities::UNLIMITED_KEY_LENGTH
            && preg_match('/^.{'.($this->maximumKeyLength + 1).',}/u', $key)
        ) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key "%s" provided; key is too long. Must be no more than %d characters',
                $key,
                $this->maximumKeyLength
            ));
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
     * @param int|DateInterval
     * @return null|int
     * @throws SimpleCacheInvalidArgumentException for invalid arguments
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
        if (is_string($ttl)
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
     * @param array|iterable $iterable
     * @param bool $useKeys Whether or not to preserve keys during conversion
     * @param string $forMethod Method that called this one; used for reporting
     *     invalid values.
     * @return array
     * @throws SimpleCacheInvalidArgumentException for invalid $iterable values
     */
    private function convertIterableToArray($iterable, $useKeys, $forMethod)
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        if (! $iterable instanceof Traversable) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid value provided to %s::%s; must be an array or Traversable',
                __CLASS__,
                $forMethod
            ));
        }

        $array = [];
        foreach ($iterable as $key => $value) {
            if (! $useKeys) {
                $array[] = $value;
                continue;
            }

            if (! is_string($key) && ! is_int($key) && ! is_float($key)) {
                throw new SimpleCacheInvalidArgumentException(sprintf(
                    'Invalid key detected of type "%s"; must be a scalar',
                    is_object($key) ? get_class($key) : gettype($key)
                ));
            }
            $array[$key] = $value;
        }
        return $array;
    }

    private function memoizeMaximumKeyLengthCapability(StorageInterface $storage, Capabilities $capabilities): void
    {
        $maximumKeyLength = $capabilities->getMaxKeyLength();

        if ($maximumKeyLength === Capabilities::UNLIMITED_KEY_LENGTH) {
            $this->maximumKeyLength = Capabilities::UNLIMITED_KEY_LENGTH;
            return;
        }

        if ($maximumKeyLength === Capabilities::UNKNOWN_KEY_LENGTH) {
            // For backward compatibility, assume adapters which do not provide a maximum key length do support 64 chars
            $maximumKeyLength = 64;
        }

        if ($maximumKeyLength < 64) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'The storage adapter "%s" does not fulfill the minimum requirements for PSR-16:'
                .' The maximum key length capability must allow at least 64 characters.',
                get_class($storage)
            ));
        }

        $this->maximumKeyLength = min($maximumKeyLength, self::PCRE_MAXIMUM_QUANTIFIER_LENGTH - 1);
    }
}
