# Introduction

Cache patterns are configurable objects that solve known performance
bottlenecks. Each should be used only in the specific situations they are
designed to address. For example, you can use the `CallbackCache`,
`ObjectCache`, or `ClassCache` patterns to cache method and function calls; to
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

Pattern objects can either be created from the provided `Laminas\Cache\PatternFactory`, or
by instantiating one of the `Laminas\Cache\Pattern\*Cache` classes.

```php
use Laminas\Cache\Pattern\CallbackCache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;

/** @var StorageInterface $storage */
$storage = null; // Can be any instance of StorageInterface

// Or the equivalent manual instantiation:
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
