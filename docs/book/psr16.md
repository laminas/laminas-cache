# PSR-16 Support

- Since 2.8.0

[PSR-16](https://www.php-fig.org/psr/psr-16/) provides a simplified approach to
cache access that does not involve cache pools, tags, deferment, etc.; it
can be thought of as a key/value storage approach to caching.

zend-cache provides PSR-16 support via the class
`Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator`. This class implements PSR-16's
`Psr\SimpleCache\CacheInterface`, and composes a
`Zend\Cache\Storage\StorageInterface` instance to which it proxies all
operations.

Instantiation is as follows:

```php
use Zend\Cache\StorageFactory;
use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

$storage = StorageFactory::factory([
    'adapter' => [
        'name'    => 'apc',
        'options' => [],
    ],
]);

$cache = new SimpleCacheDecorator($storage);
```

Once you have a `SimpleCacheDecorator` instance, you can perform operations per
that specification:

```php
// Use has() to determine whether to fetch the value or calculate it:
$value = $cache->has('someKey') ? $cache->get('someKey') : calculateValue();
if (! $cache->has('someKey')) {
    $cache->set('someKey', $value);
}

// Or use a default value:
$value = $cache->get('someKey', $defaultValue);
```

When setting values, whether single values or multiple, you can also optionally
provide a Time To Live (TTL) value. This proxies to the underlying storage
instance's options, temporarily resetting its TTL value for the duration of the
operation. TTL values may be expressed as integers (in which case they represent
seconds) or `DateInterval` instances. As examples:

```php
$cache->set('someKey', $value, 30); // set TTL to 30s
$cache->set('someKey', $value, new DateInterval('P1D'); // set TTL to 1 day

$cache->setMultiple([
    'key1' => $value1,
    'key2' => $value2,
], 3600); // set TTL to 1 hour
$cache->setMultiple([
    'key1' => $value1,
    'key2' => $value2,
], new DateInterval('P6H'); // set TTL to 6 hours
```

For more details on what methods are exposed, consult the [CacheInterface
specification](https://www.php-fig.org/psr/psr-16/#21-cacheinterface).
