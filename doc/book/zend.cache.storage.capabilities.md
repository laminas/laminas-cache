# Zend\\Cache\\Storage\\Capabilities

## Overview

Storage capabilities describes how a storage adapter works and which features it supports.

To get capabilities of a storage adapter, you can use the method `getCapabilities()` of the storage
adapter but only the storage adapter and its plugins have permissions to change them.

Because capabilities are mutable, for example, by changing some options, you can subscribe to the
"change" event to get notifications; see the examples for details.

If you are writing your own plugin or adapter, you can also change capabilities because you have
access to the marker object and can create your own marker to instantiate a new object of
`Zend\Cache\Storage\Capabilities`.

## Available Methods

## Examples

**Get storage capabilities and do specific stuff in base of it**

``` sourceCode
use Zend\Cache\StorageFactory;

$cache = StorageFactory::adapterFactory('filesystem');
$supportedDatatypes = $cache->getCapabilities()->getSupportedDatatypes();

// now you can run specific stuff in base of supported feature
if ($supportedDatatypes['object']) {
    $cache->set($key, $object);
} else {
    $cache->set($key, serialize($object));
}
```

**Listen to change event**

``` sourceCode
use Zend\Cache\StorageFactory;

$cache = StorageFactory::adapterFactory('filesystem', array(
    'no_atime' => false,
));

// Catching capability changes
$cache->getEventManager()->attach('capability', function($event) {
    echo count($event->getParams()) . ' capabilities changed';
});

// change option which changes capabilities
$cache->getOptions()->setNoATime(true);
```
