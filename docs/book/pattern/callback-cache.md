# CallbackCache

The callback cache pattern caches the results of arbitrary PHP callables.

## Quick Start

```php
use Laminas\Cache\Pattern\CallbackCache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;

/** @var StorageInterface $storage */
$storage = null; // Can be any instance of StorageInterface

// Or the equivalent manual instantiation:
$callbackCache = new CallbackCache(
    $storage,
    new PatternOptions([
        'cache_output' => true,
    ])
);
```

## Configuration Options

Option | Data Type | Default Value | Description
------ | --------- | ------------- | -----------
`storage` | `string | array | Laminas\Cache\Storage\StorageInterface` | none | **deprecated** Adapter used for reading and writing cached data.
`cache_output` | `bool` | `true` | Whether or not to cache callback output.

## Available Methods

In addition to the methods defined in the `PatternInterface` and the `StorageCapableInterface`, this
implementation provides the following methods.

```php
namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

class CallbackCache extends AbstractStorageCapablePattern
{
    /**
     * Call the specified callback or get the result from cache
     *
     * @param  callable   $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @return mixed Result
     * @throws Exception\RuntimeException if invalid cached data
     * @throws \Exception
     */
    public function call($callback, array $args = []);

    /**
     * Intercept method overloading; proxies to call()
     *
     * @param  string $function  Function name to call
     * @param  array  $args      Function arguments
     * @return mixed
     * @throws Exception\RuntimeException
     * @throws \Exception
     */
    public function __call($function, array $args);

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  callable   $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @return string
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function generateKey($callback, array $args = []);
}
```

## Examples

### Instantiating the Callback Cache Pattern

```php
use Laminas\Cache\PatternFactory;

$callbackCache = PatternFactory::factory('callback', [
    'storage' => 'apc'
]);
```
