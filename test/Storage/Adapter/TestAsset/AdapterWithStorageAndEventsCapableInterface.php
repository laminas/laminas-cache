<?php

namespace LaminasTest\Cache\Storage\Adapter\TestAsset;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventsCapableInterface;

final class AdapterWithStorageAndEventsCapableInterface implements StorageInterface, EventsCapableInterface
{

    /**
     * @inheritDoc
     */
    public function getEventManager()
    {
    }

    /**
     * @inheritDoc
     */
    public function setOptions($options)
    {
    }

    /**
     * @inheritDoc
     */
    public function getOptions()
    {
    }

    /**
     * @inheritDoc
     */
    public function getItem($key, &$success = null, &$casToken = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys)
    {
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
    }

    /**
     * @inheritDoc
     */
    public function hasItems(array $keys)
    {
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key)
    {
    }

    /**
     * @inheritDoc
     */
    public function getMetadatas(array $keys)
    {
    }

    /**
     * @inheritDoc
     */
    public function setItem($key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $keyValuePairs)
    {
    }

    /**
     * @inheritDoc
     */
    public function addItem($key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function addItems(array $keyValuePairs)
    {
    }

    /**
     * @inheritDoc
     */
    public function replaceItem($key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function replaceItems(array $keyValuePairs)
    {
    }

    /**
     * @inheritDoc
     */
    public function checkAndSetItem($token, $key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function touchItem($key)
    {
    }

    /**
     * @inheritDoc
     */
    public function touchItems(array $keys)
    {
    }

    /**
     * @inheritDoc
     */
    public function removeItem($key)
    {
    }

    /**
     * @inheritDoc
     */
    public function removeItems(array $keys)
    {
    }

    /**
     * @inheritDoc
     */
    public function incrementItem($key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function incrementItems(array $keyValuePairs)
    {
    }

    /**
     * @inheritDoc
     */
    public function decrementItem($key, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function decrementItems(array $keyValuePairs)
    {
    }

    /**
     * @inheritDoc
     */
    public function getCapabilities()
    {
    }

    public function hasPlugin(PluginInterface $plugin)
    {
        return true;
    }
}
