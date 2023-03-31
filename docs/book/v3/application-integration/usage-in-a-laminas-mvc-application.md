# Usage in a laminas-mvc Application

The following example shows _one_ potential use case of laminas-cache within a laminas-mvc based application.
The example uses a module, a controller and shows the resolving of dependencies of the controller by configuration.

## Preparation

Before starting, make sure laminas-cache is [installed and configured](../installation.md).

> MISSING: **Installation Requirements**
> laminas-cache is shipped without a specific cache adapter to allow free choice of storage backends and their dependencies.
> So make sure that the required adapters are installed.
>
> The following example used the [filesystem adapter of laminas-cache](../storage/adapter.md#filesystem-adapter):
>
> ```bash
> $ composer require laminas/laminas-cache-storage-adapter-filesystem
> ```

## Configure Cache

To configure the cache in a laminas-mvc based application, use either application or module configuration (such as `config/autoload/global.php` or `module/Application/config/module.config.php`, respectively), and define the configuration key `caches`.

This example uses the global configuration, e.g. `config/autoload/global.php`:

```php
return [
    'caches' => [
        'default-cache' => [
            'adapter' => Laminas\Cache\Storage\Adapter\Filesystem::class,
            'options' => [
                'cache_dir' => __DIR__ . '/../../data/cache',
            ],
        ],
        // …
    ],
    // ...
];
```

The factory `Laminas\Cache\Service\StorageCacheAbstractServiceFactory` uses the configuration, searches for the configuration key `caches` and creates the storage adapters using the discovered configuration.

## Create Controller

[Create a controller class](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-controller) and inject the cache with the interface for all cache storage adapters via the constructor, e.g. `module/Application/Controller/IndexController.php`:

```php
namespace Application\Controller;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Mvc\Controller\AbstractActionController;

final class IndexController extends AbstractActionController
{
    public function __construct(
        private readonly StorageInterface $cache
    ) {}
    
    public function indexAction(): array
    {
        if (! $this->cache->hasItem('example')) {
            $this->cache->addItem('example', 'value');
        }

        echo $this->cache->getItem('example') // value;
        
        // …
        
        return [];
    }
}
```

## Register Controller

To [register the controller](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-route) for the application, extend the configuration of the module.
Add the following lines to the module configuration file, e.g. `module/Application/config/module.config.php`:

<pre class="language-php" data-line="3,8"><code>
namespace Application;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => ConfigAbstractFactory::class,
        ],
    ],
    // …
];
</code></pre>

The example uses the [config factory from laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/config-abstract-factory/) which allows any string to be used to fetch a service from the application service container, like the name of the configured cache: `default-cache`.

This means that the factory [searches for an appropriate configuration](https://docs.laminas.dev/laminas-servicemanager/config-abstract-factory/#configuration) to create the controller and to resolve the constructor dependencies for the controller class.

### Add Factory Configuration For Controller

Extend the module configuration file to add the configuration for the controller.
Use the name of the cache (`default-cache`), which was previously defined in the configuration of the caches, to retrieve the related cache storage instance:

<pre class="language-php" data-line="11-15"><code>
namespace Application;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => ConfigAbstractFactory::class,
        ],
    ],
    ConfigAbstractFactory::class => [
        Controller\IndexController::class => [
            'default-cache',
        ],
    ],
    // …
];
</code></pre>

## Using Multiple Caches

The use more than one cache backend, the factory `Laminas\Cache\Service\StorageCacheAbstractServiceFactory` allows to define multiple cache storages.

Extend the cache configuration in `config/autoload/global.php` and add more cache adapters:

<pre class="language-php" data-line="9-14"><code>
return [
    'caches' => [
        'default-cache' => [
            'adapter' => Laminas\Cache\Storage\Adapter\Filesystem::class,
            'options' => [
                'cache_dir' => __DIR__ . '/../../data/cache',
            ],
        ],
        'secondary-cache' => [
            'adapter' => Laminas\Cache\Storage\Adapter\Memory::class,
        ],
        'blackhole' => [
            'adapter' => Laminas\Cache\Storage\Adapter\BlackHole::class,
        ],
        // …
    ],
    // ...
];
</code></pre>

MISSING: **Installation Requirements**
Make sure that the [used storage adapters are installed](#preparation):
```bash
$ composer require laminas/laminas-cache-storage-adapter-memory laminas/laminas-cache-storage-adapter-blackhole
```

### Change Used Adapter for Controller

To use a different cache adapter for the controller, change the related module configuration and use one of the previously defined names:

<pre class="language-php" data-line="13"><code>
namespace Application;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => ConfigAbstractFactory::class,
        ],
    ],
    ConfigAbstractFactory::class => [
        Controller\IndexController::class => [
            'blackhole',
        ],
    ],
    // …
];
</code></pre>

## Learn More

- [Storage Adapters](../storage/adapter.md)
- [Configuration-based Abstract Factory](https://docs.laminas.dev/laminas-servicemanager/config-abstract-factory/)
