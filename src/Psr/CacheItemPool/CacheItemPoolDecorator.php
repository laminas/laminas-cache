<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Psr\CacheItemPool;

use Laminas\Cache\Exception;
use Laminas\Cache\Psr\SerializationTrait;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_walk;
use function current;
use function get_class;
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
    use SerializationTrait;

    /** @var StorageInterface */
    private $storage;

    /** @var CacheItem[] */
    private $deferred = [];

    /**
     * PSR-6 requires that all implementing libraries support TTL so the given storage adapter must also support static
     * TTL or an exception will be raised. Currently the following adapters do *not* support static TTL: Dba,
     * Filesystem, Memory, Session and Redis < v2.
     *
     * @throws CacheException
     */
    public function __construct(StorageInterface $storage)
    {
        $this->validateStorage($storage);
        $this->storage = $storage;
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
    public function getItem($key)
    {
        $this->validateKey($key);

        if (! $this->hasDeferredItem($key)) {
            $value = null;
            $isHit = false;
            try {
                $value = $this->storage->getItem($key, $isHit);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface $e) {
                // ignore
            }

            return new CacheItem($key, $value, $isHit);
        }

        return clone $this->deferred[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        $this->validateKeys($keys);
        $items = [];

        // check deferred items first
        foreach ($keys as $key) {
            if ($this->hasDeferredItem($key)) {
                // dereference deferred items
                $items[$key] = clone $this->deferred[$key];
            }
        }

        $keys = array_diff($keys, array_keys($items));

        if ($keys) {
            try {
                $cacheItems = $this->storage->getItems($keys);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface $e) {
                $cacheItems = [];
            }

            foreach ($cacheItems as $key => $value) {
                $isHit       = true;
                $items[$key] = new CacheItem($key, $value, $isHit);
            }

            // Return empty items for any keys that where not found
            foreach (array_diff($keys, array_keys($cacheItems)) as $key) {
                $items[$key] = new CacheItem($key, null, false);
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        $this->validateKey($key);

        // check deferred items first
        $hasItem = $this->hasDeferredItem($key);

        if (! $hasItem) {
            try {
                $hasItem = $this->storage->hasItem($key);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface $e) {
                $hasItem = false;
            }
        }

        return $hasItem;
    }

    /**
     * {@inheritdoc}
     *
     * If the storage adapter supports namespaces and one has been set, only that namespace is cleared; otherwise
     * entire cache is flushed.
     */
    public function clear()
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
        } catch (Exception\ExceptionInterface $e) {
            $cleared = false;
        }

        return $cleared;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $this->validateKeys($keys);

        // remove deferred items first
        $this->deferred = array_diff_key($this->deferred, array_flip($keys));

        try {
            return null !== $this->storage->removeItems($keys);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface $e) {
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        return $this->saveMultipleItems([$item], $item->getTtl()) === [];
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        // deferred items should always be a 'hit' until they expire
        $item->setIsHit(true);
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        /** @var array<string,non-empty-array<string,CacheItem>> $groupedByTtl */
        $groupedByTtl = [];
        foreach ($this->deferred as $cacheKey => $item) {
            $itemTtl                = var_export($item->getTtl(), true);
            $group                  = $groupedByTtl[$itemTtl] ?? [];
            $group[$cacheKey]       = $item;
            $groupedByTtl[$itemTtl] = $group;
        }

        $notSavedItems = [];
        foreach ($groupedByTtl as $keyValuePairs) {
            $itemTtl         = current($keyValuePairs)->getTtl();
            $notSavedItems[] = $this->saveMultipleItems($keyValuePairs, $itemTtl);
        }

        $this->deferred = array_unique(array_merge([], ...$notSavedItems));

        return empty($this->deferred);
    }

    /**
     * Throws exception is storage is not compatible with PSR-6
     *
     * @throws CacheException
     * @psalm-assert-if-true StorageInterface&FlushableInterface $storage
     */
    private function validateStorage(StorageInterface $storage)
    {
        if ($this->isSerializationRequired($storage)) {
            throw new CacheException(sprintf(
                'The storage adapter "%s" requires a serializer plugin; please see'
                . ' https://docs.laminas.dev/laminas-cache/storage/plugin/#quick-start'
                . ' for details on how to attach the plugin to your adapter.',
                get_class($storage)
            ));
        }

        // all current adapters implement this
        if (! $storage instanceof FlushableInterface) {
            throw new CacheException(sprintf(
                'Storage %s does not implement %s',
                get_class($storage),
                FlushableInterface::class
            ));
        }

        // we've got to be able to set per-item TTL on write
        $capabilities = $storage->getCapabilities();
        if (! ($capabilities->getStaticTtl() && $capabilities->getMinTtl())) {
            throw new CacheException(sprintf(
                'Storage %s does not support static TTL',
                get_class($storage)
            ));
        }

        if ($capabilities->getUseRequestTime()) {
            throw new CacheException(sprintf(
                'The capability "use-request-time" of storage %s violates PSR-6',
                get_class($storage)
            ));
        }

        if ($capabilities->getLockOnExpire()) {
            throw new CacheException(sprintf(
                'The capability "lock-on-expire" of storage %s violates PSR-6',
                get_class($storage)
            ));
        }
    }

    /**
     * Returns true if deferred item exists for given key and has not expired
     *
     * @param string $key
     * @return bool
     */
    private function hasDeferredItem($key)
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
     * @param mixed $key
     * @throws InvalidArgumentException
     */
    private function validateKey($key)
    {
        if (! is_string($key) || preg_match('#[{}()/\\\\@:]#', $key)) {
            throw new InvalidArgumentException(sprintf(
                "Key must be a string and not contain '{}()/\\@:'; '%s' given",
                is_string($key) ? $key : gettype($key)
            ));
        }
    }

    /**
     * Throws exception if any of given keys is invalid
     *
     * @param array $keys
     * @throws InvalidArgumentException
     */
    private function validateKeys($keys)
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * @param array<int,CacheItem> $items
     * @psalm-param list<CacheItem> $items
     * @return array<string,CacheItem>
     */
    private function saveMultipleItems(array $items, ?int $itemTtl): array
    {
        // delete expired item
        if ($itemTtl < 0) {
            $this->deleteItems(array_map(static function (CacheItemInterface $item): string {
                return $item->getKey();
            }, $items));
            array_walk($items, static function (CacheItem $cacheItem): void {
                $cacheItem->setIsHit(false);
            });

            return $items;
        }

        $options = $this->storage->getOptions();
        $ttl     = $options->getTtl();

        $keyValuePair = [];
        $keyItemPair  = [];
        foreach ($items as $item) {
            $key                = $item->getKey();
            $keyValuePair[$key] = $item->get();
            $keyItemPair[$key]  = $item;
        }

        $options->setTtl($itemTtl ?? 0);

        $notSavedKeys = [];
        try {
            $notSavedKeys = $this->storage->setItems($keyValuePair);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface $e) {
            foreach (array_keys($keyValuePair) as $key) {
                $notSavedKeys[] = $key;
            }
        } finally {
            $options->setTtl($ttl);
        }

        $notSavedItems = [];
        foreach ($keyItemPair as $key => $item) {
            if (in_array($key, $notSavedKeys, true)) {
                $notSavedItems[] = $item;
                continue;
            }

            $item->setIsHit(true);
        }

        return $notSavedItems;
    }
}
