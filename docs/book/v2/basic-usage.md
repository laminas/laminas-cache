# Basic Usage

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
