<?php

namespace Laminas\Cache\Storage;

use Laminas\EventManager\EventsCapableInterface;
use SplObjectStorage;

interface PluginCapableInterface extends EventsCapableInterface
{
    /**
     * Check if a plugin is registered
     *
     * @param  Plugin\PluginInterface $plugin
     * @return bool
     */
    public function hasPlugin(Plugin\PluginInterface $plugin);

    /**
     * Return registry of plugins
     *
     * @return SplObjectStorage
     */
    public function getPluginRegistry();
}
