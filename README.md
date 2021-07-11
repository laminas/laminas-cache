# laminas-cache

[![Build Status](https://github.com/laminas/laminas-cache/actions/workflows/continous-integration.yml/badge.svg)](https://github.com/laminas/laminas-cache/actions/workflows/continous-integration.yml)
[![Coverage Status](https://coveralls.io/repos/github/laminas/laminas-cache/badge.svg)](https://coveralls.io/github/laminas/laminas-cache)

`Laminas\Cache` provides a general cache system for PHP. The `Laminas\Cache` component
is able to cache different patterns (class, object, output, etc) using different
storage adapters (DB, File, Memcache, etc).

- File issues at https://github.com/laminas/laminas-cache/issues
- Documentation is at https://docs.laminas.dev/laminas-cache/

## Standalone

If this component is used without `laminas-mvc` or `mezzio`, a PSR-11 container to fetch services, adapters, plugins, etc. is needed.

The easiest way would be to use [laminas-config-aggregator](https://docs.laminas.dev/laminas-config-aggregator/) along with [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/).

```php
use Laminas\Cache\ConfigProvider;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;

$config = (new ConfigAggregator([
    ConfigProvider::class,
]))->getMergedConfig();

$dependencies = $config['dependencies'];

$container = new ServiceManager($dependencies);

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

$storage = $storageFactory->create(Memory::class);

$storage->setItem('foo', 'bar');
```

## Benchmarks

We provide scripts for benchmarking laminas-cache using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be
found in the `benchmark/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```

## Avoid Unused Cache Adapters Are Being Installed

> ### Only necessary in 2.10+
>
> Starting with 3.0.0, no storage adapter is required by this component and thus, each project has to specify the storage adapters which are required by the project.
> When migrated to 3.0.0, the `replace` section is not needed anymore.

With `laminas-cache` v2.10.0, we introduced satellite packages for all cache adapters.

In case, there is no need for several adapters in your project, you can use composer to ensure these adapters are not being installed. To make this happen, you have to specify a `replace` property within the `composer.json` of your project.

### Example `composer.json` with Only Memory Adapter Being Installed

```json
{
    "name": "vendor/project",
    "description": "",
    "type": "project",
    "require": {
        "laminas/laminas-cache": "^2.10",
        "laminas/laminas-cache-storage-adapter-memory": "^1.0"
    },
    "replace": {
        "laminas/laminas-cache-storage-adapter-apc": "*",
        "laminas/laminas-cache-storage-adapter-apcu": "*",
        "laminas/laminas-cache-storage-adapter-blackhole": "*",
        "laminas/laminas-cache-storage-adapter-dba": "*",
        "laminas/laminas-cache-storage-adapter-ext-mongodb": "*",
        "laminas/laminas-cache-storage-adapter-filesystem": "*",
        "laminas/laminas-cache-storage-adapter-memcache": "*",
        "laminas/laminas-cache-storage-adapter-memcached": "*",
        "laminas/laminas-cache-storage-adapter-mongodb": "*",
        "laminas/laminas-cache-storage-adapter-redis": "*",
        "laminas/laminas-cache-storage-adapter-session": "*",
        "laminas/laminas-cache-storage-adapter-wincache": "*",
        "laminas/laminas-cache-storage-adapter-xcache": "*",
        "laminas/laminas-cache-storage-adapter-zend-server": "*"
    }
}
```

When using `composer install` on this, only the `laminas/laminas-cache-storage-adapter-memory` is being installed.

```bash
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Package operations: 10 installs, 0 updates, 0 removals
  - Installing psr/simple-cache (1.0.1): Loading from cache
  - Installing psr/cache (1.0.1): Loading from cache
  - Installing laminas/laminas-zendframework-bridge (1.2.0): Loading from cache
  - Installing laminas/laminas-stdlib (3.3.1): Loading from cache
  - Installing psr/container (1.1.1): Loading from cache
  - Installing container-interop/container-interop (1.2.0): Loading from cache
  - Installing laminas/laminas-servicemanager (3.6.4): Loading from cache
  - Installing laminas/laminas-eventmanager (3.3.1): Loading from cache
  - Installing laminas/laminas-cache-storage-adapter-memory (1.0.1): Loading from cache
  - Installing laminas/laminas-cache (2.10.1): Loading from cache
Package container-interop/container-interop is abandoned, you should avoid using it. Use psr/container instead.
Generating autoload files
6 packages you are using are looking for funding.
Use the `composer fund` command to find out more!
```
