<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage;

use Zend\Cache\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for cache storage adapters
 *
 * Enforces that adapters retrieved are instances of
 * StorageInterface. Additionally, it registers a number of default
 * adapters available.
 */
class AdapterPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'apc'            => Adapter\Apc::class,
        'blackhole'      => Adapter\BlackHole::class,
        'dba'            => Adapter\Dba::class,
        'filesystem'     => Adapter\Filesystem::class,
        'memcache'       => Adapter\Memcache::class,
        'memcached'      => Adapter\Memcached::class,
        'memory'         => Adapter\Memory::class,
        'mongodb'        => Adapter\MongoDb::class,
        'redis'          => Adapter\Redis::class,
        'session'        => Adapter\Session::class,
        'xcache'         => Adapter\XCache::class,
        'wincache'       => Adapter\WinCache::class,
        'zendserverdisk' => Adapter\ZendServerDisk::class,
        'zendservershm'  => Adapter\ZendServerShm::class
    ];

    protected $factories = [
        Adapter\Apc::class            => InvokableFactory::class,
        Adapter\BlackHole::class      => InvokableFactory::class,
        Adapter\Dba::class            => InvokableFactory::class,
        Adapter\Filesystem::class     => InvokableFactory::class,
        Adapter\Memcache::class       => InvokableFactory::class,
        Adapter\Memcached::class      => InvokableFactory::class,
        Adapter\Memory::class         => InvokableFactory::class,
        Adapter\MongoDb::class        => InvokableFactory::class,
        Adapter\Redis::class          => InvokableFactory::class,
        Adapter\Session::class        => InvokableFactory::class,
        Adapter\XCache::class         => InvokableFactory::class,
        Adapter\WinCache::class       => InvokableFactory::class,
        Adapter\ZendServerDisk::class => InvokableFactory::class,
        Adapter\ZendServerShm::class  => InvokableFactory::class
    ];

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
