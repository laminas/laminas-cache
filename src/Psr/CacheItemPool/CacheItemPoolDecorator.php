<?php

namespace Laminas\Cache\Psr\CacheItemPool;

use DateTimeZone;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\Clock;
use Laminas\Cache\Psr\MaximumKeyLengthTrait;
use Laminas\Cache\Psr\SerializationTrait;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;
use Webmozart\Assert\Assert;

use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function current;
use function date_default_timezone_get;
use function gettype;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function var_export;

/**
 * Decorate laminas-cache adapters as PSR-6 cache item pools.
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-6-cache.md
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-6-cache-meta.md
 */
class CacheItemPoolDecorator implements CacheItemPoolInterface
{
    use MaximumKeyLengthTrait;
    use SerializationTrait;

    /** @var array<string,CacheItem> */
    private array $deferred = [];

    private ClockInterface $clock;

    /**
     * PSR-6 requires that all implementing libraries support TTL so the given storage adapter must also support static
     * TTL or an exception will be raised. Currently the following adapters do *not* support static TTL: Dba,
     * Filesystem, Memory, Session and Redis < v2.
     *
     * @throws CacheException
     */
    public function __construct(
        private readonly StorageInterface&FlushableInterface $storage,
        ?ClockInterface $clock = null,
    ) {
        $this->validateStorage($storage);
        $capabilities = $storage->getCapabilities();
        $this->memoizeMaximumKeyLengthCapability($storage, $capabilities);
        $this->clock = $clock ?? new Clock(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Saves any deferred items that have not been committed
     */
    public function __destruct()
    {
        $this->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        if (! $this->hasDeferredItem($key)) {
            $value = null;
            $isHit = false;
            try {
                $value = $this->storage->getItem($key, $isHit);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface) {
                // ignore
            }

            return new CacheItem($key, $value, $isHit, $this->clock);
        }

        return clone $this->deferred[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): array
    {
        if ($keys === []) {
            return [];
        }

        $keys = array_values($keys);
        $this->validateKeys($keys);
        $items = [];

        // check deferred items first
        foreach ($keys as $key) {
            if ($this->hasDeferredItem($key)) {
                // dereference deferred items
                $items[$key] = clone $this->deferred[$key];
            }
        }

        $keys = array_values(array_diff($keys, array_keys($items)));

        if ($keys !== []) {
            try {
                $cacheItems = $this->storage->getItems($keys);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface) {
                $cacheItems = [];
            }

            foreach ($cacheItems as $key => $value) {
                $items[$key] = new CacheItem($key, $value, true, $this->clock);
            }

            // Return empty items for any keys that where not found
            foreach (array_diff($keys, array_keys($cacheItems)) as $key) {
                $items[$key] = new CacheItem($key, null, false, $this->clock);
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        // check deferred items first
        if ($this->hasDeferredItem($key)) {
            return true;
        }

        try {
            return $this->storage->hasItem($key);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * If the storage adapter supports namespaces and one has been set, only that namespace is cleared; otherwise
     * entire cache is flushed.
     */
    public function clear(): bool
    {
        $this->deferred = [];

        try {
            $options   = $this->storage->getOptions();
            $namespace = $options->getNamespace();
            if ($this->storage instanceof ClearByNamespaceInterface && $namespace) {
                $cleared = $this->storage->clearByNamespace($namespace);
            } else {
                $cleared = $this->storage->flush();
            }
        } catch (Exception\ExceptionInterface) {
            $cleared = false;
        }

        return $cleared;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        if ($keys === []) {
            return true;
        }

        $keys = array_values($keys);
        $this->validateKeys($keys);

        // remove deferred items first
        $this->deferred = array_diff_key($this->deferred, array_flip($keys));

        try {
            $result = $this->storage->removeItems($keys);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface) {
            return false;
        }

        if ($result === []) {
            return true;
        }

        $existing = $this->storage->hasItems($result);
        $unified  = array_unique($existing);
        return ! in_array(true, $unified, true);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        return $this->saveMultipleItems([$item], $item->getTtl()) === [];
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        $ttl = $item->getTtl();
        if ($ttl !== null && $ttl <= 0) {
            return false;
        }

        // deferred items should always be a 'hit' until they expire
        $item->setIsHit(true);
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $groupedByTtl = [];
        foreach ($this->deferred as $cacheKey => $item) {
            $itemTtl                = var_export($item->getTtl(), true);
            $group                  = $groupedByTtl[$itemTtl] ?? [];
            $group[$cacheKey]       = $item;
            $groupedByTtl[$itemTtl] = array_values($group);
        }

        $notSavedItems = [];
        /**
         * NOTE: we are not using the array key for the TTL in here as TTL might be `null`.
         *       Since we stringify the TTL by using `var_export`, the array has string and integer keys.
         *       Converting a string `null` back to native null-type would be a huge mess.
         */
        foreach ($groupedByTtl as $keyValuePairs) {
            $itemTtl         = current($keyValuePairs)->getTtl();
            $notSavedItems[] = $this->saveMultipleItems($keyValuePairs, $itemTtl);
        }

        $this->deferred = array_merge([], ...$notSavedItems);

        return empty($this->deferred);
    }

    /**
     * Throws exception is storage is not compatible with PSR-6
     *
     * @throws CacheException
     */
    private function validateStorage(StorageInterface $storage): void
    {
        if ($this->isSerializationRequired($storage)) {
            throw new CacheException(sprintf(
                'The storage adapter "%s" requires a serializer plugin; please see'
                . ' https://docs.laminas.dev/laminas-cache/storage/plugin/#quick-start'
                . ' for details on how to attach the plugin to your adapter.',
                $storage::class
            ));
        }

        // we've got to be able to set per-item TTL on write
        $capabilities = $storage->getCapabilities();
        if (! ($capabilities->getStaticTtl() && $capabilities->getMinTtl())) {
            throw new CacheException(sprintf(
                'Storage %s does not support static TTL',
                $storage::class
            ));
        }

        if ($capabilities->getUseRequestTime()) {
            throw new CacheException(sprintf(
                'The capability "use-request-time" of storage %s violates PSR-6',
                $storage::class
            ));
        }

        if ($capabilities->getLockOnExpire()) {
            throw new CacheException(sprintf(
                'The capability "lock-on-expire" of storage %s violates PSR-6',
                $storage::class
            ));
        }
    }

    /**
     * Returns true if deferred item exists for given key and has not expired
     */
    private function hasDeferredItem(string $key): bool
    {
        if (isset($this->deferred[$key])) {
            $ttl = $this->deferred[$key]->getTtl();
            return $ttl === null || $ttl > 0;
        }
        return false;
    }

    /**
     * Throws exception if given key is invalid
     *
     * @throws InvalidArgumentException
     * @psalm-assert non-empty-string $key
     */
    private function validateKey(mixed $key): void
    {
        if (! is_string($key) || preg_match('#[{}()/\\\\@:]#', $key)) {
            throw new InvalidArgumentException(sprintf(
                "Key must be a string and not contain '{}()/\\@:'; '%s' given",
                is_string($key) ? $key : gettype($key)
            ));
        }

        if ($key === '') {
            throw new InvalidArgumentException('Provided key must not be an empty string.');
        }

        if ($this->exceedsMaximumKeyLength($key)) {
            throw InvalidArgumentException::maximumKeyLengthExceeded($key, $this->maximumKeyLength);
        }
    }

    /**
     * Throws exception if any of given keys is invalid
     *
     * @param non-empty-list<string> $keys
     * @psalm-assert non-empty-list<non-empty-string> $keys
     * @throws InvalidArgumentException
     */
    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * @psalm-param non-empty-list<CacheItem> $items
     * @psalm-return array<string,CacheItem>
     */
    private function saveMultipleItems(array $items, ?int $itemTtl): array
    {
        $keyItemPair = [];
        foreach ($items as $item) {
            $key = $item->getKey();
            Assert::stringNotEmpty($key);
            $keyItemPair[$key] = $item;
        }

        // delete expired item
        if ($itemTtl < 0) {
            $this->deleteItems(array_keys($keyItemPair));
            foreach ($keyItemPair as $cacheItem) {
                $cacheItem->setIsHit(false);
            }

            return $keyItemPair;
        }

        $options = $this->storage->getOptions();
        $ttl     = $options->getTtl();

        $keyValuePair = [];
        foreach ($items as $item) {
            $key = $item->getKey();
            Assert::stringNotEmpty($key);
            $keyValuePair[$key] = $item->get();
        }

        $options->setTtl($itemTtl ?? 0);

        try {
            $notSavedKeys = $this->storage->setItems($keyValuePair);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface) {
            $notSavedKeys = array_keys($keyValuePair);
        } finally {
            $options->setTtl($ttl);
        }

        $notSavedItems = [];
        foreach ($keyItemPair as $key => $item) {
            if (in_array($key, $notSavedKeys, true)) {
                $notSavedItems[$key] = $item;
                continue;
            }

            $item->setIsHit(true);
        }

        return $notSavedItems;
    }
}
