# OutputCache

The `OutputCache` pattern caches output between calls to `start()` and `end()`.

## Quick Start

```php
use Laminas\Cache\Pattern\OutputCache;
use Laminas\Cache\Pattern\PatternOptions;

$outputCache = new OutputCache(
    $storage,
    new PatternOptions()
);
```

> ### Storage Adapter
>
> The `$storage` adapter can be any adapter which implements the `StorageInterface`. Check out the [Pattern Quick Start](./intro.md#quick-start)-Section for a standard adapter which can be used here.

## Configuration Options

Option | Data Type | Default Value | Description
------ | --------- | ------------- | -----------
`storage` | `string\|array\|Laminas\Cache\Storage\StorageInterface` | none | **deprecated** Adapter used for reading and writing cached data.

## Examples

### Caching simple View Scripts

```php
use Laminas\Cache\Pattern\OutputCache;
use Laminas\Cache\Pattern\PatternOptions;

$outputCache = new OutputCache(
    $storage,
    new PatternOptions()
);

$outputCache->start('mySimpleViewScript');
include '/path/to/view/script.phtml';
$outputCache->end();
```

## Available Methods

In addition to the methods defined in `PatternInterface` and `StorageCapableInterface`, this implementation
defines the following methods.

```php
namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

class OutputCache extends AbstractStorageCapablePattern
{
    /**
     * If there is a cached item with the given key, display its data, and
     * return true. Otherwise, start buffering output until end() is called, or
     * the script ends.
     *
     * @param  string  $key Key
     * @throws Exception\MissingKeyException if key is missing
     * @return bool
     */
    public function start($key);

    /**
     * Stop buffering output, write buffered data to the cache using the key
     * provided to start(), and display the buffer.
     *
     * @throws Exception\RuntimeException if output cache not started or buffering not active
     * @return bool TRUE on success, FALSE on failure writing to cache
     */
    public function end();
}
```
