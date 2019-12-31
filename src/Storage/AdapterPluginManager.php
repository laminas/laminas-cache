<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage;

use Laminas\Cache\Exception;
use Laminas\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for cache storage adapters
 *
 * Enforces that adapters retrieved are instances of
 * StorageInterface. Additionally, it registers a number of default
 * adapters available.
 *
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage Storage
 */
class AdapterPluginManager extends AbstractPluginManager
{
    /**
     * Default set of adapters
     *
     * @var array
     */
    protected $invokableClasses = array(
        'apc'            => 'Laminas\Cache\Storage\Adapter\Apc',
        'filesystem'     => 'Laminas\Cache\Storage\Adapter\Filesystem',
        'memcached'      => 'Laminas\Cache\Storage\Adapter\Memcached',
        'memory'         => 'Laminas\Cache\Storage\Adapter\Memory',
        'sysvshm'        => 'Laminas\Cache\Storage\Adapter\SystemVShm',
        'systemvshm'     => 'Laminas\Cache\Storage\Adapter\SystemVShm',
        'sqlite'         => 'Laminas\Cache\Storage\Adapter\Sqlite',
        'dba'            => 'Laminas\Cache\Storage\Adapter\Dba',
        'wincache'       => 'Laminas\Cache\Storage\Adapter\WinCache',
        'xcache'         => 'Laminas\Cache\Storage\Adapter\XCache',
        'zendserverdisk' => 'Laminas\Cache\Storage\Adapter\ZendServerDisk',
        'zendservershm'  => 'Laminas\Cache\Storage\Adapter\ZendServerShm',
    );

    /**
     * Do not share by default
     *
     * @var array
     */
    protected $shareByDefault = false;

    /**
     * Validate the plugin
     *
     * Checks that the adapter loaded is an instance of StorageInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof StorageInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s\StorageInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}
