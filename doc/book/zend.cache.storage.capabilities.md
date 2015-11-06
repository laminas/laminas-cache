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

\#\#\# \_\_construct(Zend\\Cache\\Storage\\StorageInterface $storage, stdClass $marker, array
$capabilities = array(), Zend\\Cache\\Storage\\Capabilities|null $baseCapabilities = null) :noindex:

> Constructor

\#\#\# getSupportedDatatypes() :noindex:

> Get supported datatypes.
rtype  
array
\#\#\# setSupportedDatatypes(stdClass $marker, array $datatypes) :noindex:

> Set supported datatypes.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getSupportedMetadata() :noindex:

> Get supported metadata.
rtype  
array
\#\#\# setSupportedMetadata(stdClass $marker, string $metadata) :noindex:

> Set supported metadata.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getMinTtl() :noindex:

> Get minimum supported time-to-live.
(Returning 0 means items never expire)
rtype  
integer
\#\#\# setMinTtl(stdClass $marker, int $minTtl) :noindex:

> Set minimum supported time-to-live.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getMaxTtl() :noindex:

> Get maximum supported time-to-live.
rtype  
integer
\#\#\# setMaxTtl(stdClass $marker, int $maxTtl) :noindex:

> Set maximum supported time-to-live.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getStaticTtl() :noindex:

> Is the time-to-live handled static (on write), or dynamic (on read).
rtype  
boolean
\#\#\# setStaticTtl(stdClass $marker, boolean $flag) :noindex:

> Set if the time-to-live is handled statically (on write) or dynamically (on read).
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getTtlPrecision() :noindex:

> Get time-to-live precision.
rtype  
float
\#\#\# setTtlPrecision(stdClass $marker, float $ttlPrecision) :noindex:

> Set time-to-live precision.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getUseRequestTime() :noindex:

> Get the "use request time" flag status.
rtype  
boolean
\#\#\# setUseRequestTime(stdClass $marker, boolean $flag) :noindex:

> Set the "use request time" flag.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getExpiredRead() :noindex:

> Get flag indicating if expired items are readable.
rtype  
boolean
\#\#\# setExpiredRead(stdClass $marker, boolean $flag) :noindex:

> Set if expired items are readable.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getMaxKeyLength() :noindex:

> Get maximum key length.
rtype  
integer
\#\#\# setMaxKeyLength(stdClass $marker, int $maxKeyLength) :noindex:

> Set maximum key length.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getNamespaceIsPrefix() :noindex:

> Get if namespace support is implemented as a key prefix.
rtype  
boolean
\#\#\# setNamespaceIsPrefix(stdClass $marker, boolean $flag) :noindex:

> Set if namespace support is implemented as a key prefix.
rtype  
Zend\\Cache\\Storage\\Capabilities
\#\#\# getNamespaceSeparator() :noindex:

> Get namespace separator if namespace is implemented as a key prefix.
rtype  
string
\#\#\# setNamespaceSeparator(stdClass $marker, string $separator) :noindex:

> Set the namespace separator if namespace is implemented as a key prefix.
rtype  
Zend\\Cache\\Storage\\Capabilities
## Examples

**Get storage capabilities and do specific stuff in base of it**

```php
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

```php
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
