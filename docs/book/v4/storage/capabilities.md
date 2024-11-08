# Storage Capabilities

Storage capabilities describe how a storage adapter works, and which features it
supports.

To get capabilities of a storage adapter, you can use the method
`getCapabilities()`, but only the storage adapter and its plugins have
permissions to change them.

Because capabilities are mutable, you can subscribe to the "change" event to get
notifications; see the examples for details.

If you are writing your own plugin or adapter, you can also change capabilities
because you have access to the marker object and can create your own marker to
instantiate a new instance of `Laminas\Cache\Storage\Capabilities`.

## Available Methods

```php
namespace Laminas\Cache\Storage;

use ArrayObject;
use stdClass;
use Laminas\Cache\Exception;
use Laminas\EventManager\EventsCapableInterface;

final class Capabilities
{
    /**
     * @param int<-1,max> $maxKeyLength
     * @param SupportedDataTypesArrayShape $supportedDataTypes
     */
    public function __construct(
        /**
         * Maximum supported key length for the cache backend
         */
        public readonly int $maxKeyLength,
        /**
         * Whether the cache backend supports TTL
         */
        public readonly bool $ttlSupported,
        public readonly bool $namespaceIsPrefix,
        /**
         * Contains the supported data types.
         * Depending on the cache backend in use, the type remains as is, is converted to a different type or is not
         * supported at all.
         */
        public readonly array $supportedDataTypes,
        public readonly int|float $ttlPrecision,
        public readonly bool $usesRequestTime,
    ) {
    }
}
```

## Examples

### Get Storage Capabilities and do specific Stuff based on them

```php
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

$cache = $storageFactory->create('filesystem');
$supportedDataTypes = $cache->getCapabilities()->supportedDataTypes;

// now you can run specific stuff in base of supported feature
if ($supportedDataTypes['object']) {
    $cache->set($key, $object);
} else {
    $cache->set($key, serialize($object));
}
```
