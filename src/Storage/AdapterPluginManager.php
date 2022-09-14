<?php

namespace Laminas\Cache\Storage;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for cache storage adapters
 *
 * Enforces that adapters retrieved are instances of
 * StorageInterface. Additionally, it registers a number of default
 * adapters available.
 */
final class AdapterPluginManager extends AbstractPluginManager
{
    /**
     * Do not share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /** @var string */
    protected $instanceOf = StorageInterface::class;
}
