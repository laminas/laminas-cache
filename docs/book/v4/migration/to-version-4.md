# Migration to Version 4.0

Finally, native types **everywhere**. With v4.0, `laminas-cache` depends on `laminas-servicemanager` v4 which already introduced full native types and thus, cache now has native types as well.
Along with these changes, we also decided to remove and/or enhance some features to make the usage of this component more user-friendly.
So instead of working with metadata arrays, a new `MetadataCapableInterface` was introduced which provides a generic interface for storage adapters to tell both IDEs and static analysers to understand what metadata instances are returned for which storage adapter.
This allows per-storage Metadata which can differ depending on the storage being used.

## Checklist

1. Ensure you are on latest `laminas/laminas-cache` v3
2. Ensure you are on latest `laminas/laminas-cache-storage-adapter-*` version (might differ)
3. Verify that you are **not** using one of the following methods
   1. `StorageInterface#incrementItem` (no replacement available, should be implemented in userland code)
   2. `StorageInterface#incrementItems` (no replacement available, should be implemented in userland code)
   3. `StorageInterface#decrementItem` (no replacement available, should be implemented in userland code)
   4. `StorageInterface#decrementItems` (no replacement available, should be implemented in userland code)
4. Verify that you are **not** using `supportedMetadata` capability (use `MetadataCapableInterface#getMetadata` instead)
5. Verify that you are **not** using `KeyListIterator` with mode `CURRENT_AS_METADATA` (use the returned `key` instead and pass it to the `MetadataCapable` storage adapter (**NOTE: not all adapters do implement `MetadataCapableInterface`**)
6. If you use the `Serializer` plugin
   1. Verify that if you pass a `string` as `serializer` option, you do not directly depend on the return value of `PluginOptions#getSerializer` (method will return `string` instead of instantiating a new `SerializerInterface` instance). The plugin itself can still handle `string` and an instance of `SerializerInterface` as in previous versions
7. If you provide own plugins, storage adapters, pattern, you have to upgrade to v4 and update all method/argument/property (return-) types according to the updated versions. Check out [rector](https://github.com/rectorphp/rector) which can help with this kind of migration
8. If you are handling `Laminas\Cache\Exception\MissingKeyException`, you can remove that code as the exception does not exist anymore
9. Check if you use `ObjectCache` pattern, that your code does not expect an instance of `CallbackCache` to be passed

## New Features

- Every adapter which supports `metadata` now implements `MetadataCapableInterface` and provides a dedicated object containing all the metadata values it supports
- Adds support for `psr/cache` and `psr/simple-cache` v2 & v3

## Removed Classes

- `Laminas\Cache\Exception\MissingKeyException`

## Breaking Changes

- `AbstractAdapter` and `StorageInterface` are not aware of the methods `getMetadata` anymore. These were moved to the new `MetadataCapableInterface`
- `Capabilities` do not provide `supportedMetadata` anymore. The supported metadata is tied to the used storage adapter and thus, was already requiring projects to explicitly know the exact implementation of the cache backend in case of using these metadatas anyway
- `KeyListIterator` and the corresponding `IteratorInterface` does not provide the `mode` `CURRENT_AS_METADATA` anymore
- `PluginOptions#getSerializer` does not create a serializer anymore if a `string` option was passed, instead, the `string` is returned
- Increment and decrement feature was removed from `StorageInterface`, so there is no more `StorageInterface#incrementItem`, `StorageInterface#decrementItem`, `StorageInterface#decrementItems` and `StorageInterface#incrementItems`
    - this also removes `incrementItem`, `incrementItems`, `decrementItem`, `derementItems` events (`pre`, `post` and `exception`)
- Every method now has native return types
- Every property now has native types
- Every method argument now has native types
- `ObjectCache` does not inherit the `CallbackCache` pattern anymore
