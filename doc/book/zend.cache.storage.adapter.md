# Zend\\Cache\\Storage\\Adapter

## Overview

> Storage adapters are wrappers for real storage resources such as memory and the filesystem, using
the well known adapter pattern.
They come with tons of methods to read, write and modify stored items and to get information about
stored items and the storage.
All adapters implement the interface `Zend\Cache\Storage\StorageInterface` and most extend
`Zend\Cache\Storage\Adapter\AbstractAdapter`, which comes with basic logic.
Configuration is handled by either `Zend\Cache\Storage\Adapter\AdapterOptions`, or an
adapter-specific options class if it exists. You may pass the options instance to the class at
instantiation or via the `setOptions()` method, or alternately pass an associative array of options
in either place (internally, these are then passed to an options class instance). Alternately, you
can pass either the options instance or associative array to the
`Zend\Cache\StorageFactory::factory` method.

> ## Note
#### Many methods throw exceptions
Because many caching operations throw an exception on error, you need to catch them manually or you
can use the plug-in `Zend\Cache\Storage\Plugin\ExceptionHandler` with `throw_exceptions` set to
`false` to automatically catch them. You can also define an `exception_callback` to log exceptions.

## Quick Start

> Caching adapters can either be created from the provided `Zend\Cache\StorageFactory` factory, or
by simply instantiating one of the `Zend\Cache\Storage\Adapter\*` classes.
To make life easier, the `Zend\Cache\StorageFactory` comes with a `factory` method to create an
adapter and create/add all requested plugins at once.

```php
use Zend\Cache\StorageFactory;

// Via factory:
$cache = StorageFactory::factory(array(
    'adapter' => array(
        'name'    => 'apc',
        'options' => array('ttl' => 3600),
    ),
    'plugins' => array(
        'exception_handler' => array('throw_exceptions' => false),
    ),
));

// Alternately:
$cache  = StorageFactory::adapterFactory('apc', array('ttl' => 3600));
$plugin = StorageFactory::pluginFactory('exception_handler', array(
    'throw_exceptions' => false,
));
$cache->addPlugin($plugin);

// Or manually:
$cache  = new Zend\Cache\Storage\Adapter\Apc();
$cache->getOptions()->setTtl(3600);

$plugin = new Zend\Cache\Storage\Plugin\ExceptionHandler();
$plugin->getOptions()->setThrowExceptions(false);
$cache->addPlugin($plugin);
```

## Basic Configuration Options

> The following configuration options are defined by `Zend\Cache\Storage\Adapter\AdapterOptions` and
are available for every supported adapter. Adapter-specific configuration options are described on
adapter level below.
<table
<colgroup
<col width="13%" /
<col width="24%" /
<col width="15%" /
<col width="46%" /
</colgroup
<thead
<tr class="header"
<th align="left"Option</th
<th align="left"Data Type</th
<th align="left"Default Value</th
<th align="left"Description</th
</tr
</thead
<tbody
<tr class="odd"
<td align="left"ttl</td
<td align="left"<codeinteger</code</td
<td align="left"0</td
<td align="left"Time to live</td
</tr
<tr class="even"
<td align="left"namespace</td
<td align="left"<codestring</code</td
<td align="left"&quot;zfcache&quot;</td
<td align="left"The &quot;namespace&quot; in which cache items will live</td
</tr
<tr class="odd"
<td align="left"key_pattern</td
<td align="left"<codenull</code|<codestring</code</td
<td align="left"<codenull</code</td
<td align="left"Pattern against which to validate cache keys</td
</tr
<tr class="even"
<td align="left"readable</td
<td align="left"<codeboolean</code</td
<td align="left"<codetrue</code</td
<td align="left"Enable/Disable reading data from cache</td
</tr
<tr class="odd"
<td align="left"writable</td
<td align="left"<codeboolean</code</td
<td align="left"<codetrue</code</td
<td align="left"Enable/Disable writing data to cache</td
</tr
</tbody
</table
## The StorageInterface

The `Zend\Cache\Storage\StorageInterface` is the basic interface implemented by all storage
adapters.

## The AvailableSpaceCapableInterface

The `Zend\Cache\Storage\AvailableSpaceCapableInterface` implements a method to make it possible
getting the current available space of the storage.

## The TotalSpaceCapableInterface

The `Zend\Cache\Storage\TotalSpaceCapableInterface` implements a method to make it possible getting
the total space of the storage.

## The ClearByNamespaceInterface

The `Zend\Cache\Storage\ClearByNamespaceInterface` implements a method to clear all items of a given
namespace.

## The ClearByPrefixInterface

The `Zend\Cache\Storage\ClearByPrefixInterface` implements a method to clear all items of a given
prefix (within the current configured namespace).

## The ClearExpiredInterface

The `Zend\Cache\Storage\ClearExpiredInterface` implements a method to clear all expired items
(within the current configured namespace).

## The FlushableInterface

The `Zend\Cache\Storage\FlushableInterface` implements a method to flush the complete storage.

## The IterableInterface

The `Zend\Cache\Storage\IterableInterface` implements a method to get an iterator to iterate over
items of the storage. It extends `IteratorAggregate` so it's possible to directly iterate over the
storage using `foreach`.

## The OptimizableInterface

The `Zend\Cache\Storage\OptimizableInterface` implements a method to run optimization processes on
the storage.

## The TaggableInterface

The `Zend\Cache\Storage\TaggableInterface` implements methods to mark items with one or more tags
and to clean items matching tags.

## The Apc Adapter

> The `Zend\Cache\Storage\Adapter\Apc` adapter stores cache items in shared memory through the
required PHP extension [APC](http://pecl.php.net/package/APC) (Alternative PHP Cache).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\ClearByPrefixInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\IterableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

## The Dba Adapter

> The `Zend\Cache\Storage\Adapter\Dba` adapter stores cache items into
[dbm](http://en.wikipedia.org/wiki/Dbm) like databases using the required PHP extension
[dba](http://php.net/manual/book.dba.php).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\ClearByPrefixInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\IterableInterface`
- `Zend\Cache\Storage\OptimizableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

> ## Note
#### This adapter doesn't support automatically expire items
Because of this adapter doesn't support automatically expire items it's very important to clean
outdated items by self.

## The Filesystem Adapter

> The `Zend\Cache\Storage\Adapter\Filesystem` adapter stores cache items into the filesystem.
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\ClearByPrefixInterface`
- `Zend\Cache\Storage\ClearExpiredInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\IterableInterface`
- `Zend\Cache\Storage\OptimizableInterface`
- `Zend\Cache\Storage\TaggableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

## The Memcached Adapter

> The `Zend\Cache\Storage\Adapter\Memcached` adapter stores cache items over the memcached protocol.
It's using the required PHP extension [memcached](http://pecl.php.net/package/memcached) which is
based on [Libmemcached](http://libmemcached.org/).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

## The Redis Adapter

> The `Zend\Cache\Storage\Adapter\Redis` adapter stores cache items over the redis protocol. It's
using the required PHP extension [redis](https://github.com/nicolasff/phpredis).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`

## The Memory Adapter

> The `Zend\Cache\Storage\Adapter\Memory` adapter stores cache items into the PHP process using an
array.
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByPrefixInterface`
- `Zend\Cache\Storage\ClearExpiredInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\IterableInterface`
- `Zend\Cache\Storage\TaggableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

> ## Note
All stored items will be lost after terminating the script.

## The MongoDB Adapter

> The `Zend\Cache\Storage\Adapter\MongoDB` adapter stores cache items into MongoDB, using either the
PHP extension [mongo](http://php.net/mongo) OR a MongoDB polyfill library, such as
[Mongofill](https://github.com/mongofill/mongofill).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\FlushableInterface`
## 

## The WinCache Adapter

> The `Zend\Cache\Storage\Adapter\WinCache` adapter stores cache items into shared memory through
the required PHP extension [WinCache](http://pecl.php.net/package/WinCache).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

## The XCache Adapter

> The `Zend\Cache\Storage\Adapter\XCache` adapter stores cache items into shared memory through the
required PHP extension [XCache](http://xcache.lighttpd.net/).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\ClearByPrefixInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\IterableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`
## 

## The ZendServerDisk Adapter

> This `Zend\Cache\Storage\Adapter\ZendServerDisk` adapter stores cache items on filesystem through
the [Zend Server Data Caching API](http://www.zend.com/en/products/server/).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\AvailableSpaceCapableInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`

## The ZendServerShm Adapter

> The `Zend\Cache\Storage\Adapter\ZendServerShm` adapter stores cache items in shared memory through
the [Zend Server Data Caching API](http://www.zend.com/en/products/server/).
This adapter implements the following interfaces:
- `Zend\Cache\Storage\StorageInterface`
- `Zend\Cache\Storage\ClearByNamespaceInterface`
- `Zend\Cache\Storage\FlushableInterface`
- `Zend\Cache\Storage\TotalSpaceCapableInterface`

## Examples

**Basic usage**

```php
$cache   = \Zend\Cache\StorageFactory::factory(array(
    'adapter' => array(
        'name' => 'filesystem'
    ),
    'plugins' => array(
        // Don't throw exceptions on cache errors
        'exception_handler' => array(
            'throw_exceptions' => false
        ),
    )
));
$key    = 'unique-cache-key';
$result = $cache->getItem($key, $success);
if (!$success) {
    $result = doExpensiveStuff();
    $cache->setItem($key, $result);
}
```

**Get multiple rows from db**

```php
// Instantiate the cache instance using a namespace for the same type of items
$cache   = \Zend\Cache\StorageFactory::factory(array(
    'adapter' => array(
        'name'    => 'filesystem'
        // With a namespace we can indicate the same type of items
        // -> So we can simple use the db id as cache key
        'options' => array(
            'namespace' => 'dbtable'
        ),
    ),
    'plugins' => array(
        // Don't throw exceptions on cache errors
        'exception_handler' => array(
            'throw_exceptions' => false
        ),
        // We store database rows on filesystem so we need to serialize them
        'Serializer'
    )
));

// Load two rows from cache if possible
$ids     = array(1, 2);
$results = $cache->getItems($ids);
if (count($results) < count($ids)) {
    // Load rows from db if loading from cache failed
    $missingIds     = array_diff($ids, array_keys($results));
    $missingResults = array();
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
