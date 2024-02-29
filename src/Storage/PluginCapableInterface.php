<?php

namespace Laminas\Cache\Storage;

use Laminas\EventManager\EventsCapableInterface;
use SplObjectStorage;

interface PluginCapableInterface extends EventsCapableInterface
{
    /**
     * Check if a plugin is registered
     */
    public function hasPlugin(Plugin\PluginInterface $plugin): bool;

    /**
     * Return registry of plugins
     */
    public function getPluginRegistry(): SplObjectStorage;
}
