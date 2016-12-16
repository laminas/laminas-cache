<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Zend\Cache\Psr;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Zend\Cache\Exception;
use Zend\Cache\Storage\ClearByNamespaceInterface;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\StorageInterface;

/**
 * PSR-6 cache adapter
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-6-cache.md
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-6-cache-meta.md
 */
class CacheItemPoolAdapter implements CacheItemPoolInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var CacheItem[]
     */
    private $deferred = [];

    /**
     * @var bool
     */
    private $serializeValues = false;

    /**
     * @var string
     */
    private static $serializedFalse;

    /**
     * Constructor.
     *
     * PSR-6 requires that all implementing libraries support TTL so the given storage adapter must also support static
     * TTL or an exception will be raised. Currently the following adapters do *not* support static TTL: Dba,
     * Filesystem, Memory, Session and Redis < v2.
     *
     * @param StorageInterface $storage
     *
     * @throws CacheException
     */
    public function __construct(StorageInterface $storage)
    {
        $this->validateStorage($storage);

        $this->serializeValues = $this->shouldSerialize($storage);
        if ($this->serializeValues) {
            static::$serializedFalse = serialize(false);
        }

        $this->storage = $storage;
    }

    /**
     * Destructor.
     *
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

                if ($this->serializeValues && $isHit) {
                    // will set $isHit = false if unserialization fails
                    extract($this->unserialize($value));
                }
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface $e) {
                // ignore
            }

            return new CacheItem($key, $value, $isHit);
        } else {
            return clone $this->deferred[$key];
        }
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

        if (count($keys)) {
            try {
                $cacheItems = $this->storage->getItems($keys);
            } catch (Exception\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (Exception\ExceptionInterface $e) {
                $cacheItems = [];
            }

            foreach ($cacheItems as $key => $value) {
                $isHit = true;
                if ($this->serializeValues) {
                    // will set $isHit = false if unserialization fails
                    extract($this->unserialize($value));
                }

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
            $namespace = $this->storage->getOptions()->getNamespace();
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

        $deleted = true;

        // remove deferred items first
        $this->deferred = array_diff_key($this->deferred, array_flip($keys));

        try {
            $this->storage->removeItems($keys);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface $e) {
            $deleted = false;
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        $itemTtl = $item->getTtl();

        // delete expired item
        if ($itemTtl < 0) {
            $this->deleteItem($item->getKey());
            $item->setIsHit(false);
            return false;
        }

        $saved   = true;
        $options = $this->storage->getOptions();
        $ttl     = $options->getTtl();

        try {
            // get item value and serialize, if required
            $value = $item->get();
            if ($this->serializeValues) {
                $value = serialize($value);
            }

            // reset TTL on adapter, if required
            if ($itemTtl > 0) {
                $options->setTtl($itemTtl);
            }

            $saved = $this->storage->setItem($item->getKey(), $value);
            // saved items are a hit? see integration test CachePoolTest::testIsHit()
            $item->setIsHit($saved);
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception\ExceptionInterface $e) {
            $saved = false;
        } finally {
            $options->setTtl($ttl);
        }

        return $saved;
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
        $notSaved = [];

        foreach ($this->deferred as &$item) {
            if (! $this->save($item)) {
                $notSaved[] = $item;
            }
        }
        $this->deferred = $notSaved;

        return empty($this->deferred);
    }

    /**
     * Throws exception is storage is not compatible with PSR-6
     * @param StorageInterface $storage
     * @throws CacheException
     */
    private function validateStorage(StorageInterface $storage)
    {
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
     * Returns true if capabilities indicate values should be serialized before saving to preserve data types
     * @param StorageInterface $storage
     * @return bool
     */
    private function shouldSerialize(StorageInterface $storage)
    {
        $capabilities = $storage->getCapabilities();
        $requiredTypes = ['string', 'integer', 'double', 'boolean', 'NULL', 'array', 'object'];
        $types = $capabilities->getSupportedDatatypes();
        foreach ($requiredTypes as $type) {
            // 'object' => 'object' is OK
            // 'integer' => 'string' is not (redis)
            // 'integer' => 'integer' is not (memcache)
            if (! (isset($types[$type]) && in_array($types[$type], [true, 'array', 'object'], true))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Unserializes value, marking isHit false if it fails
     * @param $value
     * @return array
     */
    private function unserialize($value)
    {
        if ($value == static::$serializedFalse) {
            return ['value' => false, 'isHit' => true];
        }

        if (false === ($value = unserialize($value))) {
            return ['value' => null, 'isHit' => false];
        }

        return ['value' => $value, 'isHit' => true];
    }

    /**
     * Returns true if deferred item exists for given key and has not expired
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
     * @param array $keys
     * @throws InvalidArgumentException
     */
    private function validateKeys($keys)
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }
}
