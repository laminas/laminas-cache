<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter\TestAsset;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventsCapableInterface;

interface AdapterWithStorageAndEventsCapableInterface extends StorageInterface, EventsCapableInterface
{
    public function hasPlugin(PluginInterface $plugin): bool;

    /**
     * @param int $priority
     */
    public function addPlugin(PluginInterface $plugin, $priority = 1);
}
