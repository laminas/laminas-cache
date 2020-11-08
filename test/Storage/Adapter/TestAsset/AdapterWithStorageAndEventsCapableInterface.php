<?php

namespace LaminasTest\Cache\Storage\Adapter\TestAsset;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventsCapableInterface;

interface AdapterWithStorageAndEventsCapableInterface extends StorageInterface, EventsCapableInterface
{
    public function hasPlugin(PluginInterface $plugin): bool;
    public function addPlugin(PluginInterface $plugin, $priority = 1);
}
