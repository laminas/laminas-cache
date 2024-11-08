# Adapters

Storage adapters are wrappers for real storage resources such as memory or the filesystem, using
the well known *adapter* pattern.

They come with tons of methods to read, write, and modify stored items, and to get information about
stored items and the storage.

All adapters implement `Laminas\Cache\Storage\StorageInterface`, and most extend
`Laminas\Cache\Storage\Adapter\AbstractAdapter`, which provides a foundation of
common logic.

Configuration is handled by either `Laminas\Cache\Storage\Adapter\AdapterOptions`,
or an adapter-specific options class if it exists. You may pass the options
instance to the class at instantiation, via the `setOptions()` method, or,
alternately, pass an associative array of options in either place (internally,
these are then passed to an options class instance). Alternately, you can pass associative array to the
`Laminas\Cache\Service\StorageAdapterFactoryInterface::create` method.

## Quick Start

Caching adapters can either be created from the provided
`Laminas\Cache\Service\StorageAdapterFactoryInterface`, or by instantiating one of the
`Laminas\Cache\Storage\Adapter\*` classes.  To make life easier, the
`Laminas\Cache\Service\StorageAdapterFactoryInterface` comes with a `create()` method to create an adapter
and all requested plugins at once.

```php
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

// Via factory:
$cache = $storageFactory->create(
    'apcu',
    ['ttl' => 3600],
    [
        [
            'name' => 'exception_handler',
            'options' => [
                'throw_exceptions' => false,
             ], 
        ],
    ]
);

// Via array configuration:
$cache = $storageFactory->createFromArrayConfiguration([
    'adapter' => 'apcu',
    'options' => ['ttl' => 3600],
    'plugins' => [
        [
            'name' => 'exception_handler',
            'options' => [
                'throw_exceptions' => false,
             ], 
        ],
    ],
]);

// Alternately, create the adapter and plugin separately:
$cache  = $storageFactory->create('apcu', ['ttl' => 3600]);
$pluginFactory = $container->get(StoragePluginFactoryInterface::class);
$plugin = $pluginFactory->create('exception_handler', [
    'throw_exceptions' => false,
]);
$cache->addPlugin($plugin);

// Or do it completely manually:
$cache  = new Laminas\Cache\Storage\Adapter\Apcu();
$cache->getOptions()->setTtl(3600);

$plugin = new Laminas\Cache\Storage\Plugin\ExceptionHandler();
$plugin->getOptions()->setThrowExceptions(false);
$cache->addPlugin($plugin);
```

> ### Many Methods throw Exceptions
>
> Because many caching operations throw an exception on error, you need to catch
> them. You can do so manually, or you can use the plugin
> `Laminas\Cache\Storage\Plugin\ExceptionHandler` with `throw_exceptions` set to
> `false` to automatically catch them. You can also define an
> `exception_callback` to log exceptions.

## Basic Configuration Options

The following configuration options are defined by `Laminas\Cache\Storage\Adapter\AdapterOptions` and
are available for every supported adapter. Adapter-specific configuration options are described on
adapter level below.

| Option        | Data Type      | Default Value    | Description                                    |
|---------------|----------------|------------------|------------------------------------------------|
| `ttl`         | `integer`      | `0`              | Time to live                                   |
| `namespace`   | `string`       | `"laminascache"` | The “namespace” in which cache items will live |
| `key_pattern` | `null\|string` | `null`           | Pattern against which to validate cache keys   |
| `readable`    | `boolean`      | `true`           | Enable/Disable reading data from cache         |
| `writable`    | `boolean`      | `true`           | Enable/Disable writing data to cache           |

## StorageInterface

`Laminas\Cache\Storage\StorageInterface` is the basic interface implemented by all
storage adapters.

```php
namespace Laminas\Cache\Storage;

use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Traversable;

interface StorageInterface
{
    public function setOptions(AdapterOptions|iterable $options): self;

    public function getOptions(): AdapterOptions;

    /**
     * @param  non-empty-string  $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getItem(string $key, bool|null &$success = null, mixed &$casToken = null): mixed;

    /**
     * @param  non-empty-list<non-empty-string> $keys
     * @return array<non-empty-string,mixed> Associative array of keys and values
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getItems(array $keys);

    /**
     * Test if an item exists.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function hasItem(string $key): bool;

    /**
     * Test multiple items.
     *
     * @param  non-empty-array<non-empty-string> $keys
     * @return list<non-empty-string> Array of found keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function hasItems(array $keys): array;

    /**
     * Store an item.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function setItem(string $key, mixed $value): bool;

    /**
     * Store multiple items.
     *
     * @param  non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function setItems(array $keyValuePairs): array;

    /**
     * Add an item.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function addItem(string $key, mixed $value): bool;

    /**
     * Add multiple items.
     *
     * @param  non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function addItems(array $keyValuePairs): array;

    /**
     * Replace an existing item.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function replaceItem(string $key, mixed $value): bool;

    /**
     * Replace multiple existing items.
     *
     * @param  non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function replaceItems(array $keyValuePairs): array;

    /**
     * Set an item only if token matches
     *
     * It uses the token received from getItem() to check if the item has
     * changed before overwriting it.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     * @see    getItem()
     * @see    setItem()
     */
    public function checkAndSetItem(mixed $token, string $key, mixed $value): bool;

    /**
     * Reset lifetime of an item
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function touchItem(string $key): bool;

    /**
     * Reset lifetime of multiple items.
     *
     * @param  non-empty-list<non-empty-string> $keys
     * @return list<non-empty-string> Array of not updated keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function touchItems(array $keys): array;

    /**
     * Remove an item.
     *
     * @param  non-empty-string $key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function removeItem(string $key): bool;

    /**
     * Remove multiple items.
     *
     * @param  non-empty-array<non-empty-string> $keys
     * @return list<non-empty-string> Array of not removed keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function removeItems(array $keys): array;

    public function getCapabilities(): Capabilities;
}
```

## AvailableSpaceCapableInterface

`Laminas\Cache\Storage\AvailableSpaceCapableInterface` implements a method to allow
retrieving the current available space remaining in storage.

```php
namespace Laminas\Cache\Storage;

interface AvailableSpaceCapableInterface
{
    /**
     * Get available space in bytes
     */
    public function getAvailableSpace(): int;
}
```

## TotalSpaceCapableInterface

`Laminas\Cache\Storage\TotalSpaceCapableInterface` implements a method to allow
retrieving the total storage space.

```php
namespace Laminas\Cache\Storage;

interface TotalSpaceCapableInterface
{
    /**
     * Get total space in bytes
     */
    public function getTotalSpace(): int;
}
```

## ClearByNamespaceInterface

`Laminas\Cache\Storage\ClearByNamespaceInterface` implements a method to allow
clearing all cached items within a given namespace.

```php
namespace Laminas\Cache\Storage;

interface ClearByNamespaceInterface
{
    /**
     * Remove items of given namespace
     *
     * @param non-empty-string $namespace
     */
    public function clearByNamespace(string $namespace): bool;
}
```

## ClearByPrefixInterface

`Laminas\Cache\Storage\ClearByPrefixInterface` implements a method to allow
clearing all cached items that have a given prefix (within the currently
configured namespace).

```php
namespace Laminas\Cache\Storage;

interface ClearByPrefixInterface
{
    /**
     * Remove items matching given prefix
     *
     * @param non-empty-string $prefix
     */
    public function clearByPrefix(string $prefix): bool;
}
```

## ClearExpiredInterface

`Laminas\Cache\Storage\ClearExpiredInterface` implements a method to allow clearing
all expired items (within the current configured namespace).

```php
namespace Laminas\Cache\Storage;

interface ClearExpiredInterface
{
    /**
     * Remove expired items
     */
    public function clearExpired(): bool;
}
```

## FlushableInterface

`Laminas\Cache\Storage\FlushableInterface` implements a method for flushing the
entire cache storage.

```php
namespace Laminas\Cache\Storage;

interface FlushableInterface
{
    /**
     * Flush the whole storage
     */
    public function flush(): bool;
}
```

## IterableInterface

`Laminas\Cache\Storage\IterableInterface` implements a method for retrieving an
iterator of all items in storage. It extends `IteratorAggregate`, so it's
possible to directly iterate over the storage implementations that implement
this interface using `foreach`.

```php
namespace Laminas\Cache\Storage;

use IteratorAggregate;

/**
 * @template-covariant TKey
 * @template-covariant TValue
 * @template-extends IteratorAggregate<TKey,TValue>
 */
interface IterableInterface extends IteratorAggregate
{
    public function getIterator(): IteratorInterface;
}
```

## OptimizableInterface

`Laminas\Cache\Storage\OptimizableInterface` implements a method for running
optimization processes on the storage adapter.

```php
namespace Laminas\Cache\Storage;

interface OptimizableInterface
{
    /**
     * Optimize the storage
     */
    public function optimize(): bool;
}
```

## TaggableInterface

`Laminas\Cache\Storage\TaggableInterface` implements methods for tagging items, and
cleaning (expiring) items matching tags.

```php
namespace Laminas\Cache\Storage;

interface TaggableInterface
{
    /**
     * Set tags to an item by given key.
     * An empty array will remove all tags.
     *
     * @param non-empty-string   $key
     * @param string[] $tags
     */
    public function setTags(string $key, array $tags): bool;

    /**
     * Get tags of an item by given key
     *
     * @param non-empty-string $key
     * @return string[]|false
     */
    public function getTags(string $key): array|false;

    /**
     * Remove items matching given tags.
     *
     * If $disjunction only one of the given tags must match
     * else all given tags must match.
     *
     * @param string[] $tags
     */
    public function clearByTags(array $tags, bool $disjunction = false): bool;
}
```

## APCu Adapter

`Laminas\Cache\Storage\Adapter\Apcu` stores cache items in shared memory through the
PHP extension [APCu](http://pecl.php.net/package/APCu) (Alternative PHP Cache).

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\StorageInterface`
- `Laminas\Cache\Storage\AvailableSpaceCapableInterface`
- `Laminas\Cache\Storage\ClearByNamespaceInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\IterableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                                                 |
|----------------------|---------------------------------------------------------------------------------------|
| `supportedDatatypes` | `null`, `bool`, `int`, `float`, `string`, `array` (serialized), `object` (serialized) |
| `ttlSupported`       | `true`                                                                                |
| `ttlPrecision`       | `1`                                                                                   |
| `usesRequestTime`    | value of `apc.use_request_time` INI value, disabled by default.                       |
| `maxKeyLength`       | `5182`                                                                                |
| `namespaceIsPrefix`  | `true`                                                                                |

### Metadata

The APCu adapter does provide a couple of metadatas, which can be fetched by by using either `MetadataCapableInterface#getMetadata` or `MetadataCapableInterface#getMetadatas`.

It will return an object with the following properties (or null):

| Metadata           | Type     | Description                                                              |
|--------------------|----------|:-------------------------------------------------------------------------|
| `internalKey`      | `string` | The internal key used to store the cache item                            |
| `lastAccessTime`   | `int`    | The time the cache item was last accessed                                |
| `creationTime`     | `int`    | The time the cache item was created                                      |
| `lastModifiedTime` | `int`    | The time the cache item was last modified                                |
| `size`             | `int`    | The size the cache item is consuming within the cache                    |
| `hits`             | `int`    | The amount of times the item was requested and returned from the backend |
| `timeToLive`       | `int`    | The overall time to live (in seconds) the cache item was persisted for   |

### Adapter Specific Options

| Name                  | Data Type | Default Value | Description                               |
|-----------------------|-----------|---------------|-------------------------------------------|
| `namespace_separator` | `string`  | ":"           | A separator for the namespace and prefix. |

## BlackHole Adapter

`Laminas\Cache\Storage\Adapter\BlackHole` **does not** store any cache items. This adapter is useful to bypass caching behavior. This might be the case in development mode or unit testing.

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\StorageInterface`
- `Laminas\Cache\Storage\AvailableSpaceCapableInterface`
- `Laminas\Cache\Storage\ClearByNamespaceInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\ClearExpiredInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\IterableInterface`
- `Laminas\Cache\Storage\OptimizableInterface`
- `Laminas\Cache\Storage\TaggableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                       |
|----------------------|-------------------------------------------------------------|
| `supportedDatatypes` | `null`, `bool`, `int`, `float`, `string`, `array`, `object` |
| `ttlSupported`       | `true`                                                      |
| `ttlPrecision`       | `1`                                                         |
| `usesRequestTime`    | `false`                                                     |
| `maxKeyLength`       | unlimited as nothing will be cached anyways                 |
| `namespaceIsPrefix`  | `true`                                                      |

## Filesystem Adapter

`Laminas\Cache\Storage\Adapter\Filesystem` stores cache items on the filesystem.

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\StorageInterface`
- `Laminas\Cache\Storage\AvailableSpaceCapableInterface`
- `Laminas\Cache\Storage\ClearByNamespaceInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\ClearExpiredInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\IterableInterface`
- `Laminas\Cache\Storage\OptimizableInterface`
- `Laminas\Cache\Storage\TaggableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                                                             |
|----------------------|---------------------------------------------------------------------------------------------------|
| `supportedDatatypes` | `string`, `null` => `string`, `boolean` => `string`, `integer` => `string`, `double` => `string`  |
| `ttlSupported`       | `true`                                                                                            |
| `ttlPrecision`       | `1`                                                                                               |
| `usesRequestTime`    | `false`                                                                                           |
| `maxKeyLength`       | `249` (this is the maximum, but depending on the namespace being used, the length might be lower) |
| `namespaceIsPrefix`  | `true`                                                                                            |

### Metadata

| Metadata           | Type        | Description                                                       |
|--------------------|-------------|:------------------------------------------------------------------|
| `lastAccessTime`   | `int\|null` | The time the cache item was last accessed                         |
| `creationTime`     | `int\|null` | The time the cache item was created                               |
| `lastModifiedTime` | `int\|null` | The time the cache item was last modified                         |
| `filesize`         | `int\|null` | The amount of bytes the cache item is consuming in the filesystem |
| `filespec`         | `string`    | The absolute path to the cache file without suffix                |

### Adapter Specific Options

| Name                     | Data Type                               | Default Value          | Description                                                                                                             |
|--------------------------|-----------------------------------------|------------------------|-------------------------------------------------------------------------------------------------------------------------|
| `namespace_separator`    | `string`                                | ":"                    | A separator for the namespace and prefix                                                                                |
| `cache_dir`              | `string`                                | ""                     | Directory to store cache files.                                                                                         |
| `clear_stat_cache`       | `boolean`                               | `true`                 | Call `clearstatcache()` enabled?                                                                                        |
| `dir_level`              | `integer`                               | `1`                    | Defines how much sub-directories should be created.                                                                     |
| `dir_permission`         | `integer\|false`                        | `0700`                 | Set explicit permission on creating new directories.                                                                    |
| `file_locking`           | `boolean`                               | `true`                 | Lock files on writing.                                                                                                  |
| `file_permission`        | `integer`                               | `false`                | 0600    Set explicit permission on creating new files.                                                                  |
| `key_pattern`            | `string`                                | `/^[a-z0-9_\+\-]*$/Di` | Validate key against pattern.                                                                                           |
| `no_atime`               | `boolean`                               | `true`                 | Don’t get `fileatime` as `atime` on metadata.                                                                           |
| `no_ctime`               | `boolean`                               | `true`                 | Don’t get `filectime` as `ctime` on metadata.                                                                           |
| `umask`                  | `integer\|false`                        | `false`                | Use [umask](http://wikipedia.org/wiki/Umask) to set file and directory permissions.                                     |
| `unserializable_classes` | `boolean\|non-empty-list<class-string>` | `true`                 | A list of classes which are allowed for unserialization when reading cache values. Available as of v3.0 of the adapter. |

## Memcached Adapter

`Laminas\Cache\Storage\Adapter\Memcached` stores cache items over the memcached
protocol, using the PHP extension [memcached](http://pecl.php.net/package/memcached),
based on [Libmemcached](http://libmemcached.org/).

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\StorageInterface`
- `Laminas\Cache\Storage\AvailableSpaceCapableInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                                                         |
|----------------------|-----------------------------------------------------------------------------------------------|
| `supportedDatatypes` | `null`, `boolean`, `integer`, `double`, `string`, `array` (serialized), `object` (serialized) |
| `ttlSupported`       | `true`                                                                                        |
| `ttlPrecision`       | `1`                                                                                           |
| `usesRequestTime`    | `false`                                                                                       |
| `maxKeyLength`       | `255`                                                                                         |
| `namespaceIsPrefix`  | `true`                                                                                        |

### Adapter Specific Options

| Name          | Data Type | Default Value | Description                                                                                                                                                                                                                                                                               |
|---------------|-----------|---------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `servers`     | `array`   | `[]`          | List of servers in the format `[] = [string host, integer port]`                                                                                                                                                                                                                          |
| `lib_options` | `array`   | `[]`          | Associative array of Libmemcached options where the array key is the option name (without the prefix `OPT_`) or the constant value. The array value is the option value. Please read [the memcached setOption() page](http://php.net/manual/memcached.setoption.php) for more information |

## Redis Adapter

`Laminas\Cache\Storage\Adapter\Redis` stores cache items over the [Redis](https://redis.io) protocol
using the PHP extension [PhpRedis](https://github.com/phpredis/phpredis).

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\ClearByNamespaceInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                 |
|----------------------|-------------------------------------------------------|
| `supportedDatatypes` | `string`, `array` (serialized), `object` (serialized) |
| `ttlSupported`       | `true`                                                |
| `ttlPrecision`       | `1`                                                   |
| `usesRequestTime`    | `false`                                               |
| `maxKeyLength`       | `512000000` (in Redis v3+, `255` in older versions)   |
| `namespaceIsPrefix`  | `true`                                                |

### Metadata

| Metadata              | Type        | Description                                                                                                               |
|-----------------------|-------------|:--------------------------------------------------------------------------------------------------------------------------|
| `remainingTimeToLive` | `int\|null` | The amount of time (seconds) the cache item will remain in the cache. Will be `null` in case the cache item won't expire. |

### Adapter Specific Options

| Name                  | Data Type       | Default Value | Description                                                                                 |
|-----------------------|-----------------|---------------|---------------------------------------------------------------------------------------------|
| `database`            | `integer`       | 0             | Set database identifier.                                                                    |
| `lib_options`         | `array`         | `[]`          | Associative array of Redis options where the array key is the option name.                  |
| `namespace_separator` | `string`        | ":"           | A separator for the namespace and prefix.                                                   |
| `password`            | `string`        | ""            | Set password.                                                                               |
| `persistent_id`       | `string`        |               | Set persistent id (name of the connection, leave blank to not use a persistent connection). |
| `resource_manager`    | `string`        | ""            | Set the Redis resource manager to use                                                       |
| `server`              | `string\|array` | ""            | See below.                                                                                  |

`server` can be described as any of the following:

- URI: `/path/to/sock.sock`
- Associative array: `['host' => <host>[, 'port' => <port>[, 'timeout' => <timeout>]]]`
- List: `[<host>[, <port>, [, <timeout>]]]`

## RedisCluster Adapter

`Laminas\Cache\Storage\Adapter\RedisCluster` stores cache items over the [Redis cluster](https://github.com/phpredis/phpredis#redis-cluster-support) protocol
using the PHP extension [PhpRedis](https://github.com/phpredis/phpredis).

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\ClearByNamespaceInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\FlushableInterface`

### Capabilities

| Capability           | Value                                                 |
|----------------------|-------------------------------------------------------|
| `supportedDatatypes` | `string`, `array` (serialized), `object` (serialized) |
| `ttlSupported`       | `true`                                                |
| `ttlPrecision`       | `1`                                                   |
| `usesRequestTime`    | `false`                                               |
| `maxKeyLength`       | `512000000` (in Redis v3+, `255` in older versions)   |
| `namespaceIsPrefix`  | `true`                                                |

### Metadata

| Metadata              | Type        | Description                                                                                                               |
|-----------------------|-------------|:--------------------------------------------------------------------------------------------------------------------------|
| `remainingTimeToLive` | `int\|null` | The amount of time (seconds) the cache item will remain in the cache. Will be `null` in case the cache item won't expire. |

### Adapter Specific Options

| Name                  | Data Type | Default Value | Description                                                                                                                                                                                                                                        |
|-----------------------|-----------|---------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `lib_options`         | `array`   | `[]`          | Associative array of Redis options where the array key is the options constant value (see `RedisCluster::OPT_*` [constants](https://github.com/JetBrains/phpstorm-stubs/blob/master/redis/RedisCluster.php) for details).                          |
| `namespace_separator` | `string`  | ":"           | A separator for the namespace and prefix.                                                                                                                                                                                                          |
| `password`            | `string`  | ""            | Password to authenticate with Redis server                                                                                                                                                                                                         |
| `name`                | `string`  | ""            | Name to determine configuration from [php.ini](https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#loading-a-cluster-configuration-by-name) (**MUST NOT** be combined with `seeds`)                                                 |
| `seeds`               | `array`   | `[]`          | List of strings containing `<hostname>:<port>` (**MUST NOT** be combined with `name`)                                                                                                                                                              |
| `timeout`             | `float`   | `1.0`         | Timeout for commands, see [PhpRedis](https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#timeouts) timeouts documentation for more background.                                                                                      |
| `read_timeout`        | `float`   | `2.0`         | Read timeout for commands, see [PhpRedis](https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#timeouts) timeouts documentation for more background.                                                                                 |
| `persistent`          | `bool`    | `false`       | Flag to specify whether to create a persistent connection or not                                                                                                                                                                                   |
| `version`             | `string`  | ""            | The Redis server version. **MUST** be specified in a [Semantic Versioning 2.0.0](https://semver.org/#semantic-versioning-200) format. This information is used to determine some features/capabilities without opening a connection to the server. |

## Memory Adapter

The `Laminas\Cache\Storage\Adapter\Memory` stores items in-memory in the current
process only.

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\StorageInterface`
- `Laminas\Cache\Storage\AvailableSpaceCapableInterface`
- `Laminas\Cache\Storage\ClearByPrefixInterface`
- `Laminas\Cache\Storage\ClearExpiredInterface`
- `Laminas\Cache\Storage\FlushableInterface`
- `Laminas\Cache\Storage\IterableInterface`
- `Laminas\Cache\Storage\TaggableInterface`
- `Laminas\Cache\Storage\TotalSpaceCapableInterface`

### Capabilities

| Capability           | Value                                                                           |
|----------------------|---------------------------------------------------------------------------------|
| `supportedDatatypes` | `string`, `null`, `boolean`, `integer`, `double`, `array`, `object`, `resource` |
| `ttlSupported`       | `true`                                                                          |
| `ttlPrecision`       | `1`                                                                             |
| `usesRequestTime`    | `false`                                                                         |
| `maxKeyLength`       | `0` (unlimited)                                                                 |
| `namespaceIsPrefix`  | `false`                                                                         |

### Metadata

| Metadata           | Type    | Description                               |
|--------------------|---------|:------------------------------------------|
| `lastModifiedTime` | `float` | The time the cache item was last modified |

### Adapter Specific Options

| Name        | Data Type | Default Value   | Description                                                                                         |
|-------------|-----------|-----------------|-----------------------------------------------------------------------------------------------------|
| `max_items` | `int`     | `0` (unlimited) | Limit of how many cache items are allowed to be stored. Available with v3.0 of the `Memory` adapter |

> #### Max Items
>
> The adapter has the following behavior in regard to the `max_items` option:
>
> - If the items persisted to the memory cache are exceeding the limit, a new item will be stored while the oldest item will be removed

> ### Current process only
>
> All stored items will be lost on termination of the script. For web-facing
> requests, this typically means the cache is volatile.

## ExtMongoDB Adapter

`Laminas\Cache\Storage\Adapter\ExtMongoDB` stores cache items using the mongodb extension, and
requires that the MongoDB PHP Client library is also installed. You can install the client
library using the following:

```bash
$ composer require mongodb/mongodb
```

This adapter implements the following interfaces:

- `Laminas\Cache\Storage\FlushableInterface`

### Capabilities

| Capability           | Value                                                     |
|----------------------|-----------------------------------------------------------|
| `supportedDatatypes` | `string`, `null`, `boolean`, `integer`, `double`, `array` |
| `ttlSupported`       | `true`                                                    |
| `ttlPrecision`       | `1`                                                       |
| `usesRequestTime`    | `false`                                                   |
| `maxKeyLength`       | `255`                                                     |
| `namespaceIsPrefix`  | `true`                                                    |

### Metadata

| Metadata   | Type     | Description                                  |
|------------|----------|:---------------------------------------------|
| `objectId` | `string` | The primary key within the mongo collection. |

### Adapter Specific Options

| Name                  | Data Type | Default Value | Description                                                          |
|-----------------------|-----------|---------------|----------------------------------------------------------------------|
| `lib_option`          | `array`   |               | Associative array of options where the array key is the option name. |
| `namespace_separator` | `string`  | ":"           | A separator for the namespace and prefix.                            |

Available keys for `lib_option` include:

| Key                 | Default                                 | Description                                                                                                                                                                                                                                              |
|---------------------|-----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `server`            | `mongodb://localhost:27017`             | The MongoDB server connection string (see the [MongoDB\\Client docs](https://docs.mongodb.com/php-library/current/reference/method/MongoDBClient__construct/)).                                                                                          |
| `database`          | `laminas`                               | Name of the database to use; MongoDB will create this database if it does not exist.                                                                                                                                                                     |
| `collection`        | `cache`                                 | Name of the collection to use; MongoDB will create this collection if it does not exist.                                                                                                                                                                 |
| `connectionOptions` | `['fsync' => false, 'journal' => true]` | Associative array of URI options (such as authentication credentials or query string parameters) to pass to `MongoDB\\Client` (see the [MongoDB\\Client docs](https://docs.mongodb.com/php-library/current/reference/method/MongoDBClient__construct/)). |
| `driverOptions`     | `[]`                                    | Associative array of driver options to pass to `MongoDB\\Client` (see the [MongoDB\\Client docs](https://docs.mongodb.com/php-library/current/reference/method/MongoDBClient__construct/)).                                                              |

## Examples

### Basic Usage

```php
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

$cache = $storageFactory->create(
    'filesystem', 
    [], 
    [
        // Don't throw exceptions on cache errors
        [
            'name' => 'exception_handler',
            'options' => [
                'throw_exceptions' => false
            ],
        ],
    ]
);

$key    = 'unique-cache-key';
$result = $cache->getItem($key, $success);
if (! $success) {
    $result = doExpensiveStuff();
    $cache->setItem($key, $result);
}
```

### Get multiple Rows from a Database

```php
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

// Instantiate the cache instance using a namespace for the same type of items
$cache = $storageFactory->create(
    'filesystem',
    // With a namespace, we can indicate the same type of items,
        // so we can simply use the database id as the cache key
    [
        'namespace' => 'dbtable',
    ],
    [
        // Don't throw exceptions on cache errors
        [
            'name' => 'exception_handler',
            'options' => [
                'throw_exceptions' => false,
            ],
        ],
        // We store database rows on filesystem so we need to serialize them
        [
            'name' => 'Serializer',
        ],
    ]
);

// Load two rows from cache if possible
$ids     = [1, 2];
$results = $cache->getItems($ids);
if (count($results) < count($ids)) {
    // Load rows from db if loading from cache failed
    $missingIds     = array_diff($ids, array_keys($results));
    $missingResults = [];
    $query          = 'SELECT * FROM dbtable WHERE id IN (' . implode(',', $missingIds) . ')';
    foreach ($pdo->query($query, PDO::FETCH_ASSOC) as $row) {
        $missingResults[ $row['id'] ] = $row;
    }

    // Update cache items of the loaded rows from db
    $cache->setItems($missingResults);

    // merge results from cache and db
    $results = array_merge($results, $missingResults);
}
```
