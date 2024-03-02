<?php

namespace Laminas\Cache\Storage;

use Laminas\Cache\Exception\ExceptionInterface;

interface StorageInterface
{
    /**
     * Set options.
     */
    public function setOptions(iterable|Adapter\AdapterOptions $options): self;

    /**
     * Get options
     */
    public function getOptions(): Adapter\AdapterOptions;

    /* reading */
    /**
     * Get an item.
     *
     * @param non-empty-string $key
     * @param-out bool $success
     * @return mixed Data on success, null on failure
     * @throws ExceptionInterface
     */
    public function getItem(string $key, bool|null &$success = null, mixed &$casToken = null): mixed;

    /**
     * Get multiple items.
     *
     * @param non-empty-list<non-empty-string> $keys
     * @return array<non-empty-string,mixed> Associative array of keys and values
     * @throws ExceptionInterface
     */
    public function getItems(array $keys): array;

    /**
     * Test if an item exists.
     *
     * @param non-empty-string $key
     * @throws ExceptionInterface
     */
    public function hasItem(string $key): bool;

    /**
     * Test multiple items.
     *
     * @param non-empty-list<non-empty-string> $keys
     * @return list<non-empty-string> Array of found keys
     * @throws ExceptionInterface
     */
    public function hasItems(array $keys): array;

    /* writing */
    /**
     * Store an item.
     *
     * @param non-empty-string $key
     * @throws ExceptionInterface
     */
    public function setItem(string $key, mixed $value): bool;

    /**
     * Store multiple items.
     *
     * @param non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws ExceptionInterface
     */
    public function setItems(array $keyValuePairs): array;

    /**
     * Add an item.
     *
     * @throws ExceptionInterface
     */
    public function addItem(string $key, mixed $value): bool;

    /**
     * Add multiple items.
     *
     * @param non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws ExceptionInterface
     */
    public function addItems(array $keyValuePairs): array;

    /**
     * Replace an existing item.
     *
     * @param non-empty-string $key
     * @throws ExceptionInterface
     */
    public function replaceItem(string $key, mixed $value): bool;

    /**
     * Replace multiple existing items.
     *
     * @param non-empty-array<non-empty-string,mixed> $keyValuePairs
     * @return list<non-empty-string> Array of not stored keys
     * @throws ExceptionInterface
     */
    public function replaceItems(array $keyValuePairs): array;

    /**
     * Set an item only if token matches
     *
     * It uses the token received from getItem() to check if the item has
     * changed before overwriting it.
     *
     * @see    getItem()
     * @see    setItem()
     *
     * @param non-empty-string $key
     *
     * @throws ExceptionInterface
     */
    public function checkAndSetItem(mixed $token, string $key, mixed $value): bool;

    /**
     * Reset lifetime of an item
     *
     * @param non-empty-string $key
     * @throws ExceptionInterface
     */
    public function touchItem(string $key): bool;

    /**
     * Reset lifetime of multiple items.
     *
     * @param non-empty-list<non-empty-string> $keys
     * @return list<non-empty-string> Array of not updated keys
     * @throws ExceptionInterface
     */
    public function touchItems(array $keys): array;

    /**
     * Remove an item.
     *
     * @param non-empty-string $key
     * @throws ExceptionInterface
     */
    public function removeItem(string $key): bool;

    /**
     * Remove multiple items.
     *
     * @param non-empty-list<non-empty-string> $keys
     * @return list<non-empty-string> Array of not removed keys
     * @throws ExceptionInterface
     */
    public function removeItems(array $keys): array;

    /* status */

    /**
     * Capabilities of this storage
     */
    public function getCapabilities(): Capabilities;
}
