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
// Via the factory:
$callbackCache = Laminas\Cache\PatternFactory::factory('callback', [
    'storage' => 'apc',
]);

// Or the equivalent manual instantiation:
$callbackCache = new Laminas\Cache\Pattern\CallbackCache();
$callbackCache->setOptions(new Laminas\Cache\Pattern\PatternOptions([
    'storage' => 'apc',
]));
```

## Available Methods

The following methods are implemented by `Laminas\Cache\Pattern\AbstractPattern`.
Please read documentation of specific patterns to get more information.

```php
namespace Laminas\Cache\Pattern;

interface PatternInterface
{
    /**
     * Set pattern options
     *
     * @param  PatternOptions $options
     * @return PatternInterface
     */
    public function setOptions(PatternOptions $options);

    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions();
}
```
