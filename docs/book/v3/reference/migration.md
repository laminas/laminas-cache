# Migration to Version 3.0

Upgrading to `laminas-cache` will require a few code-changes, depending on how the storage adapters were used. Please note that the migration guide assumes that `laminas-cache` is [installed via Composer](installation.md).

Please check out the [checklist](#checklist) to see what changes need to be done.

The biggest change with `laminas-cache` v3.0 is that this component will be shipped without a specific cache adapter. The idea behind this is, that projects can freely choose those cache adapters they really use.

In the past, most of the adapters Laminas does officially support were not properly tested, blocked PHP version upgrades (due to extensions lacking compatibility) and had to be maintained due to backwards compatibility. Starting with v3.0, all adapters can be maintained and evolve independently.

## Checklist

1. `laminas-cache` is updated to the latest version from within `2.x` (currently `2.13.2`)
2. [`laminas-cli` is installed](https://docs.laminas.dev/laminas-cli/) to verify the configuration integrity (`vendor/bin/laminas laminas-cache:deprecation:check-storage-factory-config`); in case you don't want to use `laminas-cli`, please check out the [normalized array configuration](https://github.com/laminas/laminas-cache/releases/tag/2.12.0) example in the release notes of 2.12.0
3. `laminas-cache` is required with `^3.0` within `composer.json`
4. Cache adapters which are used within the project needs to be required in at least `^2.0`; in case you don't know which adapters are in use, either check your project configuration or search for the `Laminas\Cache\Storage\Adapter` namespace in your projects source code. Every adapter has to be listed in either your `module.config.php` (laminas-mvc) or `config.php` (mezzio) configuration. 
5. Project does not use any of the [removed classes and traits](#removed-classes-and-traits)
6. Storage adapters are not extended in any way as [all adapters are `final`](#breaking-changes) starting with v2.0 of the individual adapter component

## New Features

- Each cache adapter has its [own package](#satellite-packages).
- Support for PHP 8.1

## Removed Classes and Traits

With `laminas-cache` v3, some classes/traits were removed as well:

- Due to the switch to [satellite packages](#satellite-packages), the `StorageFactory` was replaced with the `StorageAdapterFactoryInterface`. Due to the same reason, the `PatternFactory` was removed. Cache patterns mostly need an underlying adapter which have to be created with the new `StorageAdapterFactoryInterface`
- `ClassCache` cache pattern was removed. For more details, please read the [related issue](https://github.com/laminas/laminas-cache/issues/107).
- `PatternCacheFactory` and `StoragePatternCacheFactory` which were introduced in v2.12.0 to provide forward compatibility.
- `PatternPluginManager` and `PatternPluginManagerFactory` which were removed due to the fact that most cache patterns require underlying cache adapter and thus are not instantiable from their name and options.
- `PluginManagerLookupTrait` which was used to provide forward compatibility for the `StorageAdapterFactoryInterface`.
- `PatternOptions` are not capable of the `storage` option anymore

## Breaking Changes

- `CallbackCache`, `OutputCache` and `ObjectCache` now require the underlying cache adapter (`StorageInterface`) as 1st `__construct` dependency. The options can be passed via 2nd `__construct)[https://github.com/CallbackCache`, `OutputCache` and `ObjectCache` now require the underlying cache adapter (`StorageInterface` arguments but are optional.  
**Please note that it is not possible to inject the pattern configuration as an array anymore**
- Storage configurations must be in a specific shape. For more details, head to the release notes of [2.12.0](https://github.com/laminas/laminas-cache/releases/tag/2.12.0)
- All cache adapters are now marked as `final` and are not extensible anymore. In case that you are extending one of the cache adapters, please switch change your code as `composition` should be preferred over inheritance. For an example, please check out the [composition over inheritance](#composition-over-inheritance) section.

## Satellite Packages

Starting with laminas-cache v3, we are introducing satellite packages for each cache backend.

In order to make this package work, you have to specify at least **one** satellite package.

A list of available cache adapters can be found here (starting with their v2 release):


- [laminas/laminas-cache-storage-adapter-apcu](https://github.com/laminas/laminas-cache-storage-adapter-apcu)
- [laminas/laminas-cache-storage-adapter-blackhole](https://github.com/laminas/laminas-cache-storage-adapter-blackhole)
- [laminas/laminas-cache-storage-adapter-ext-mongodb](https://github.com/laminas/laminas-cache-storage-adapter-ext-mongodb) 
- [laminas/laminas-cache-storage-adapter-filesystem](https://github.com/laminas/laminas-cache-storage-adapter-filesystem) 
- [laminas/laminas-cache-storage-adapter-memcached](https://github.com/laminas/laminas-cache-storage-adapter-memcached)
- [laminas/laminas-cache-storage-adapter-memory](https://github.com/laminas/laminas-cache-storage-adapter-memory)
- [laminas/laminas-cache-storage-adapter-redis](https://github.com/laminas/laminas-cache-storage-adapter-redis) 
- [laminas/laminas-cache-storage-adapter-session](https://github.com/laminas/laminas-cache-storage-adapter-session)

## Composition Over Inheritance

In case you are extending one of the cache implementations, your code might look as follows:


```php
use Laminas\Cache\Storage\Adapter\Filesystem;

class MyFileystemStorage extends Filesystem
{
    protected function internalSetItem(&$normalizedKey,&$value)
    {
        $value = doSomethingWithValue($value);
        $normalizedKey = doSOmethingWithKey($normalizedKey);
        return parent::internalSetItem($normalizedKey,$value);
    }
} 
```

If this looks familiar, using composition would look like this:

```php
use Laminas\Cache\Exception;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\Filesystem;
use Laminas\Cache\Storage\Adapter\FilesystemOptions;

final class MyFilesytemStorage extends AbstractAdapter
{
    /**
    * @var \Laminas\Cache\Storage\Adapter\Filesystem
    */
    private $filesystem;
    
    public function __construct(Filesystem $filesystem) 
    {
        $this->filesystem = $filesystem;
        parent::__construct();
    }
    
    protected function internalGetItem(&$normalizedKey,&$success = null,&$casToken = null)
    {
        return $this->filesystem->getItem(
            $normalizedKey, 
            $success,
            $casToken
        );
    }
    
    protected function internalSetItem(&$normalizedKey,&$value)
    {
        $value = doSomethingWithValue($value);
        $normalizedKey = doSomethingWithValue($normalizedKey);
        return $this->filesystem->setItem(
            $normalizedKey, 
            $value
        );
    }
    
    protected function internalRemoveItem(&$normalizedKey)
    {
        return $this->filesystem->removeItem(
            $normalizedKey
        );
    }   
}
```

Even tho, that this is more code to add/change than the previous solution, this gives the maintainers of the cache adapters more freedom to provide more stable adapter implementations.

If this does not fit your requirements, please let us know via the `laminas-cache` [repository on GitHub](https://github.com/laminas/laminas-cache) and tell us more about your implementations. Maybe your addition should be part of our official adapter or could be provided as a dedicated Plugin instead.

# StorageFactory Dependency

In case your code heavily depends on `StorageFactory` (or if you are using not yet compatible laminas components (e.g. `laminas-i18n`, ...), Laminas got your back.
With `laminas/laminas-cache-storage-deprecated-factory`, the `StorageFactory` is retained to create a temporary backwards compatibility layer.
