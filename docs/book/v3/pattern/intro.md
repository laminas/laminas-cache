# Introduction

Cache patterns are configurable objects that solve known performance
bottlenecks. Each should be used only in the specific situations they are
designed to address. For example, you can use the `CallbackCache` or
`ObjectCache` patterns to cache method and function calls; to
cache output generation, the `OutputCache` pattern could assist.

All cache patterns implement `Laminas\Cache\Pattern\PatternInterface`, and most
extend the abstract class `Laminas\Cache\Pattern\AbstractPattern`, which provides
common logic.

Configuration is provided via the `Laminas\Cache\Pattern\PatternOptions` class,
which can be instantiated with an associative array of options passed to the
constructor. To configure a pattern object, you can provide a
`Laminas\Cache\Pattern\PatternOptions` instance to the `setOptions()` method, or
provide your options (either as an associative array or `PatternOptions`
instance) to the second argument of the factory.

It's also possible to use a single instance of
`Laminas\Cache\Pattern\PatternOptions` and pass it to multiple pattern objects.

## Quick Start

Pattern objects can be created
by instantiating one of the `Laminas\Cache\Pattern\*Cache` classes.

> ### Standard Storage Adapter for Documentation
>
> A cache adapter needs a storage adapter. To be able to follow the examples in the documentation, the [adapter for the filesystem](https://docs.laminas.dev/laminas-cache/storage/adapter/#filesystem-adapter) or the [BlackHole adapter](https://docs.laminas.dev/laminas-cache/storage/adapter/#blackhole-adapter) can be used, for example.
>
> ```php
> $storage = new Laminas\Cache\Storage\Adapter\Filesystem();
> // or
> $storage = new Laminas\Cache\Storage\Adapter\BlackHole();
> ```

```php
use Laminas\Cache\Pattern\CallbackCache;
use Laminas\Cache\Pattern\PatternOptions;

$callbackCache = new CallbackCache(
    $storage,
    new PatternOptions()
);
```

## Available Methods

The following methods are implemented by every cache pattern.
Please read documentation of specific patterns to get more information.

```php
namespace Laminas\Cache\Pattern;

interface PatternInterface
{

    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions();
}
```

There are cache patterns which depend on a storage. In this case, these adapters implement the `StorageCapableInterface`:

```php
namespace Laminas\Cache\Pattern;

use Laminas\Cache\Storage\StorageInterface;

interface StorageCapableInterface extends PatternInterface
{
    public function getStorage(): ?StorageInterface;
}
```
