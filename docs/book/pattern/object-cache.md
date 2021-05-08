# ObjectCache

The `ObjectCache` pattern is an extension to the `CallbackCache` pattern. It has
the same methods, but instead caches output from any instance method calls or
public properties.

## Quick Start

```php
use Laminas\Cache\Pattern\ObjectCache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;
use stdClass;

/** @var StorageInterface $storage */
$storage = null; // Can be any instance of StorageInterface

$object      = new stdClass();
$objectCache = new ObjectCache(
    $storage,
    new PatternOptions([
      'object'  => $object,
    ])
);
```

## Configuration Options

Option | Data Type | Default Value | Description
------ | --------- | ------------- | -----------
`storage` | `string\|array\|Laminas\Cache\Storage\StorageInterface` | none | **deprecated** Adapter used for reading and writing cached data.
`object` | `object` | none | The object for which to cache method calls.
`object_key` | `null\|string` | Class name of object | Hopefully unique!
`cache_output` | `bool` | `true` | Whether or not to cache method output.
`cache_by_default` | `bool` | `true` | Cache all method calls by default.
`object_cache_methods` | `array` | `[]` | List of methods to cache (if `cache_by_default` is disabled).
`object_non_cache_methods` | `array` | `[]` | List of methods to blacklist (if `cache_by_default` is enabled).
`object_cache_magic_properties` | `bool` | `false` | Whether or not to cache properties exposed by method overloading.

## Available Methods

In addition to the methods defined in `PatternInterface` and the `StorageCapableInterface`, this implementation
defines the following methods.

```php
namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

class ObjectCache extends CallbackCache
{
    /**
     * Call and cache a class method
     *
     * @param  string $method  Method name to call
     * @param  array  $args    Method arguments
     * @return mixed
     * @throws Exception\RuntimeException
     * @throws \Exception
     */
    public function call($method, array $args = []);

    /**
     * Method overloading: proxies to call().
     *
     * @param  string $method  Method name to call
     * @param  array  $args    Method arguments
     * @return mixed
     * @throws Exception\RuntimeException
     * @throws \Exception
     */
    public function __call($method, array $args);

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  string     $method  The method
     * @param  array      $args    Callback arguments
     * @return string
     * @throws Exception\RuntimeException
     */
    public function generateKey($method, array $args = []);

    /**
     * Property overloading: write data to a named property.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __set
     * and removes cached data of previous __get and __isset calls.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __set($name, $value);

    /**
     * Property overloading: read data from a named property.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __get.
     *
     * @param  string $name
     * @return mixed
     * @see http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get($name);

    /**
     * Property overloading: check if a named property exists.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it calls __get.
     *
     * @param  string $name
     * @return bool
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name);

    /**
     * Property overloading: unset a named property.
     *
     * NOTE:
     * Magic properties will be cached too if the option cacheMagicProperties
     * is enabled and the property doesn't exist in real. If so it removes
     * previous cached __isset and __get calls.
     *
     * @param  string $name
     * @return void
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __unset($name);

    /**
     * Handle casting to string
     *
     * @return string
     * @see    http://php.net/manual/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString();

    /**
     * Intercept and cache invokable usage.
     *
     * @return mixed
     * @see    http://php.net/manual/language.oop5.magic.php#language.oop5.magic.invoke
     */
    public function __invoke();
}
```

## Examples

### Caching a Filter

```php
use Laminas\Cache\Pattern\ObjectCache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;

/** @var StorageInterface $storage */
$storage      = null; // Can be any instance of StorageInterface
$filter       = new \Laminas\Filter\RealPath();
$cachedFilter = new ObjectCache(
    $storage,
    new PatternOptions([
        'object'     => $filter,
        'object_key' => 'RealpathFilter',
        
        // The realpath filter doesn't output anything
        // so the output don't need to be caught and cached
        'cache_output' => false,
    ])
);

$path = $cachedFilter->call("filter", ['/www/var/path/../../mypath']);

// OR
$path = $cachedFilter->filter('/www/var/path/../../mypath');
```
