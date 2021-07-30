# Plugins

Cache storage plugins are objects that provide additional functionality to or
influence behavior of a storage adapter.

The plugins listen to events the adapter triggers, and can:

- change the arguments provided to the method triggering the event (via `*.post` events)
- skip and directly return a result (by calling `stopPropagation`)
- change the result (by calling `setResult` on the provided `Laminas\Cache\Storage\PostEvent`)
- catch exceptions (by reacting to `Laminas\Cache\Storage\ExceptionEvent`)

## Quick Start

Storage plugins can either be created from
`Laminas\Cache\Service\StoragePluginFactoryInterface::create()`, or by instantiating one of the
`Laminas\Cache\Storage\Plugin\*` classes.

To make life easier, `Laminas\Cache\Service\StoragePluginFactoryInterface::create()` can create both the
requested adapter and all specified plugins at once.

```php
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

// All at once:
$cache = $storageFactory->create(
    'filesystem', 
    [], 
    [
        ['name' => 'serializer'],
    ]
);

// Alternately, via discrete factory methods:
$cache  = $storageFactory->create('filesystem');

$pluginFactory = $container->get(StoragePluginFactoryInterface::class);
$plugin = $pluginFactory->create('serializer');
$cache->addPlugin($plugin);

// Or manually:
$cache  = new Laminas\Cache\Storage\Adapter\Filesystem();
$plugin = new Laminas\Cache\Storage\Plugin\Serializer();
$cache->addPlugin($plugin);
```

## The ClearExpiredByFactor Plugin

`Laminas\Cache\Storage\Plugin\ClearExpiredByFactor` calls the storage method
`clearExpired()` randomly (by factor) after every call of `setItem()`,
`setItems()`, `addItem()`, and `addItems()`.

### Plugin specific Options

Name | Data Type | Default Value | Description
---- | --------- | ------------- | -----------
`clearing_factor` | `integer` | `0` | The automatic clearing factor.

> ### Adapter must implement ClearExpiredInterface
>
> The storage adapter must implement `Laminas\Cache\Storage\ClearExpiredInterface`
> to work with this plugin.

## The ExceptionHandler Plugin

`Laminas\Cache\Storage\Plugin\ExceptionHandler` catches all exceptions thrown on
reading from or writing to the cache, and sends the exception to a defined callback function.
You may also configure the plugin to re-throw exceptions.

### Plugin specific Options

Name | Data Type | Default Value | Description
---- | --------- | ------------- | -----------
`exception_callback` | `callable|null` | null | Callback to invoke on exception; receives the exception as the sole argument.
`throw_exceptions` | `boolean` | `true` | Re-throw caught exceptions.

## The IgnoreUserAbort Plugin

`Laminas\Cache\Storage\Plugin\IgnoreUserAbort` ignores user-invoked script
termination when, allowing cache write operations to complete first.

### Plugin specific Options

Name | Data Type | Default Value | Description
---- | --------- | ------------- | -----------
`exit_on_abort` | `boolean` | `true` | Terminate script execution on user abort.

## The OptimizeByFactor Plugin

`Laminas\Cache\Storage\Plugin\OptimizeByFactor` calls the storage method `optimize()`
randomly (by factor) after removing items from the cache.

### Plugin specific Options

Name | Data Type | Default Value | Description
---- | --------- | ------------- | -----------
`optimizing_factor` | `integer` | `0` | The automatic optimization factor.

> ### Adapter must implement OptimizableInterface
>
> The storage adapter must implement `Laminas\Cache\Storage\OptimizableInterface`
> to work with this plugin.

## The Serializer Plugin

`Laminas\Cache\Storage\Plugin\Serializer` will serialize data when writing to
cache, and deserialize when reading. This allows storing datatypes not supported
by the underlying storage adapter.

### Plugin specific Options

Name | Data Type | Default Value | Description
---- | --------- | ------------- | -----------
`serializer` | `null|string|Laminas\Serializer\Adapter\AdapterInterface` | `null` | The serializer to use; see below.
`serializer_options` | `array` | `[]` | Array of options to use when instantiating the specified serializer.

The `serializer` value has two special cases:

- When `null`, the default serializer is used (JSON).
- When a `string`, the value will be pulled via
  `Laminas\Serializer\AdapterPluginManager`, with the provided
  `serializer_options`.

## Available Methods

The following methods are available to all `Laminas\Cache\Storage\Plugin\PluginInterface` implementations:

```php
namespace Laminas\Cache\Storage\Plugin;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;

interface PluginInterface extends ListenerAggregateInterface
{
    /**
     * Set options
     *
     * @param  PluginOptions $options
     * @return PluginInterface
     */
    public function setOptions(PluginOptions $options);

    /**
     * Get options
     *
     * @return PluginOptions
     */
    public function getOptions();

    /**
     * Attach listeners; inherited from ListenerAggregateInterface.
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events);

    /**
     * Detach listeners; inherited from ListenerAggregateInterface.
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events);
}
```

## Examples

### Basic Plugin Implementation

```php
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\Plugin\AbstractPlugin;
use Laminas\EventManager\EventManagerInterface;

class MyPlugin extends AbstractPlugin
{
    protected $handles = [];

    /**
     * Attach to all events this plugin is interested in.
     */
    public function attach(EventManagerInterface $events)
    {
        $this->handles[] = $events->attach('getItem.pre', array($this, 'onGetItemPre'));
        $this->handles[] = $events->attach('getItem.post', array($this, 'onGetItemPost'));
    }

    /**
     * Detach all handlers this plugin previously attached.
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->handles as $handle) {
           $events->detach($handle);
        }
        $this->handles = [];
    }

    public function onGetItemPre(Event $event)
    {
        $params = $event->getParams();
        echo sprintf("Method 'getItem' with key '%s' started\n", $params['key']);
    }

    public function onGetItemPost(Event $event)
    {
        $params = $event->getParams();
        echo sprintf("Method 'getItem' with key '%s' finished\n", $params['key']);
    }
}

// After defining this plugin, we can instantiate and add it to an adapter
// instance:
$plugin = new MyPlugin();
$cache->addPlugin($plugin);

// Now when calling getItem(), our plugin should print the expected output:
$cache->getItem('cache-key');
// Method 'getItem' with key 'cache-key' started
// Method 'getItem' with key 'cache-key' finished
```
