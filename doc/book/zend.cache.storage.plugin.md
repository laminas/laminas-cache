# Zend\\Cache\\Storage\\Plugin

## Overview

Cache storage plugins are objects to add missing functionality or to influence behavior of a storage
adapter.

The plugins listen to events the adapter triggers and can change called method arguments (\*.post -
events), skipping and directly return a result (using `stopPropagation`), changing the result (with
`setResult` of `Zend\Cache\Storage\PostEvent`) and catching exceptions (with
`Zend\Cache\Storage\ExceptionEvent`).

## Quick Start

Storage plugins can either be created from `Zend\Cache\StorageFactory` with the `pluginFactory`, or
by simply instantiating one of the `Zend\Cache\Storage\Plugin\*`classes.

To make life easier, the `Zend\Cache\StorageFactory` comes with the method `factory` to create an
adapter and all given plugins at once.

```php
use Zend\Cache\StorageFactory;

// Via factory:
$cache = StorageFactory::factory(array(
    'adapter' => 'filesystem',
    'plugins' => array('serializer'),
));

// Alternately:
$cache  = StorageFactory::adapterFactory('filesystem');
$plugin = StorageFactory::pluginFactory('serializer');
$cache->addPlugin($plugin);

// Or manually:
$cache  = new Zend\Cache\Storage\Adapter\Filesystem();
$plugin = new Zend\Cache\Storage\Plugin\Serializer();
$cache->addPlugin($plugin);
```

## The ClearExpiredByFactor Plugin

> The `Zend\Cache\Storage\Plugin\ClearExpiredByFactor` plugin calls the storage method
`clearExpired()` randomly (by factor) after every call of `setItem()`, `setItems()`, `addItem()` and
`addItems()`.

> ## Note
#### The ClearExpiredInterface is required
The storage have to implement the `Zend\Cache\Storage\ClearExpiredInterface` to work with this
plugin.

## The ExceptionHandler Plugin

> The `Zend\Cache\Storage\Plugin\ExceptionHandler` plugin catches all exceptions thrown on reading
or writing to cache and sends the exception to a defined callback function.
It's configurable if the plugin should re-throw the catched exception.

## The IgnoreUserAbort Plugin

> The `Zend\Cache\Storage\Plugin\IgnoreUserAbort` plugin ignores script terminations by users until
write operations to cache finished.

## The OptimizeByFactor Plugin

> The `Zend\Cache\Storage\Plugin\OptimizeByFactor` plugin calls the storage method `optimize()`
randomly (by factor) after removing items from cache.

> ## Note
#### The OptimizableInterface is required
The storage have to implement the `Zend\Cache\Storage\OptimizableInterface` to work with this
plugin.

## The Serializer Plugin

> The `Zend\Cache\Storage\Plugin\Serializer` plugin will serialize data on writing to cache and
unserialize on reading. So it's possible to store different datatypes into cache storages only
support strings.

## Available Methods

## Examples

**Basics of writing an own storage plugin**

```php
use Zend\Cache\Storage\Event;
use Zend\Cache\Storage\Plugin\AbstractPlugin;
use Zend\EventManager\EventManagerInterface;

class MyPlugin extends AbstractPlugin
{

    protected $handles = array();

    // This method have to attach all events required by this plugin
    public function attach(EventManagerInterface $events)
    {
        $this->handles[] = $events->attach('getItem.pre', array($this, 'onGetItemPre'));
        $this->handles[] = $events->attach('getItem.post', array($this, 'onGetItemPost'));
        return $this;
    }

    // This method have to detach all events required by this plugin
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->handles as $handle) {
           $events->detach($handle);
        }
        $this->handles = array();
        return $this;
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

// After defining this basic plugin we can instantiate and add it to an adapter instance
$plugin = new MyPlugin();
$cache->addPlugin($plugin);

// Now on calling getItem our basic plugin should print the expected output
$cache->getItem('cache-key');
// Method 'getItem' with key 'cache-key' started
// Method 'getItem' with key 'cache-key' finished
```
