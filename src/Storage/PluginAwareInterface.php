<?php

namespace Laminas\Cache\Storage;

use Laminas\Cache\Exception;

interface PluginAwareInterface extends PluginCapableInterface
{
    /**
     * Register a plugin
     *
     * @throws Exception\LogicException
     */
    public function addPlugin(Plugin\PluginInterface $plugin, int $priority = 1): StorageInterface&PluginAwareInterface;

    /**
     * Unregister an already registered plugin
     *
     * @throws Exception\LogicException
     */
    public function removePlugin(Plugin\PluginInterface $plugin): StorageInterface&PluginAwareInterface;
}
