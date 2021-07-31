# laminas-cache

[![Build Status](https://github.com/laminas/laminas-cache/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas/laminas-cache/actions/workflows/continuous-integration.yml)

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
