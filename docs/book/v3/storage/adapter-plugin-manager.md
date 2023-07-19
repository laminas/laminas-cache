# Adapter Plugin Manager

The `AdapterPluginManager` extends the laminas-servicemanager `AbstractPluginManager`, and has the following behaviors:

- It will only return `Laminas\Cache\Storage\StorageInterface` instances.
- All services are not shared by default; a new instance will be created each time you call `get()`.

## Factory

`Laminas\Cache\Storage\AdapterPluginManager` is mapped to the factory `Laminas\Cache\Service\StorageAdapterPluginManagerFactory` when wired to the dependency injection container.

The factory will be automatically registered when loading/installing the `Laminas\Cache` module in `laminas-mvc` and/or loading/installing the `ConfigProvider` into a Mezzio application.

Since version 3.11.0, the factory will look for the `config` service, and use the `storage_adapters` configuration key to seed it with additional services.
This configuration key should map to an array that follows [standard laminas-servicemanager configuration](https://docs.laminas.dev/laminas-servicemanager/configuring-the-service-manager/).

To add your own storage adapter you can add the following configuration:

```php
// config/autoload/storage_adapters.global.php
return [
    'storage_adapters' => [
        'factories' => [
            \App\MyCustomStorageAdapter::class => \App\Container\MyCustomStorageAdapterFactory::class,
        ],
    ],
];
```
