<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Zend\Cache\Storage\Adapter\Apc;
use Zend\Cache\Storage\Adapter\Apcu;
use Zend\Cache\Storage\Adapter\BlackHole;
use Zend\Cache\Storage\Adapter\Dba;
use Zend\Cache\Storage\Adapter\ExtMongoDb;
use Zend\Cache\Storage\Adapter\Filesystem;
use Zend\Cache\Storage\Adapter\Memcache;
use Zend\Cache\Storage\Adapter\Memcached;
use Zend\Cache\Storage\Adapter\Memory;
use Zend\Cache\Storage\Adapter\MongoDb;
use Zend\Cache\Storage\Adapter\Redis;
use Zend\Cache\Storage\Adapter\Session;
use Zend\Cache\Storage\Adapter\WinCache;
use Zend\Cache\Storage\Adapter\XCache;
use Zend\Cache\Storage\Adapter\ZendServerDisk;
use Zend\Cache\Storage\Adapter\ZendServerShm;

/**
 * Plugin manager implementation for cache storage adapters
 *
 * Enforces that adapters retrieved are instances of
 * StorageInterface. Additionally, it registers a number of default
 * adapters available.
 */
class AdapterPluginManager extends AbstractPluginManager
{
    /** @var array<string,string> */
    protected $aliases = [
        'apc'              => Adapter\Apc::class,
        'Apc'              => Adapter\Apc::class,
        'APC'              => Adapter\Apc::class,
        'apcu'             => Adapter\Apcu::class,
        'ApcU'             => Adapter\Apcu::class,
        'Apcu'             => Adapter\Apcu::class,
        'APCu'             => Adapter\Apcu::class,
        'black_hole'       => Adapter\BlackHole::class,
        'blackhole'        => Adapter\BlackHole::class,
        'blackHole'        => Adapter\BlackHole::class,
        'BlackHole'        => Adapter\BlackHole::class,
        'dba'              => Adapter\Dba::class,
        'Dba'              => Adapter\Dba::class,
        'DBA'              => Adapter\Dba::class,
        'ext_mongo_db'     => Adapter\ExtMongoDb::class,
        'extmongodb'       => Adapter\ExtMongoDb::class,
        'ExtMongoDb'       => Adapter\ExtMongoDb::class,
        'ExtMongoDB'       => Adapter\ExtMongoDb::class,
        'extMongoDb'       => Adapter\ExtMongoDb::class,
        'extMongoDB'       => Adapter\ExtMongoDb::class,
        'filesystem'       => Adapter\Filesystem::class,
        'Filesystem'       => Adapter\Filesystem::class,
        'memcache'         => Adapter\Memcache::class,
        'Memcache'         => Adapter\Memcache::class,
        'memcached'        => Adapter\Memcached::class,
        'Memcached'        => Adapter\Memcached::class,
        'memory'           => Adapter\Memory::class,
        'Memory'           => Adapter\Memory::class,
        'mongo_db'         => Adapter\MongoDb::class,
        'mongodb'          => Adapter\MongoDb::class,
        'MongoDb'          => Adapter\MongoDb::class,
        'MongoDB'          => Adapter\MongoDb::class,
        'mongoDb'          => Adapter\MongoDb::class,
        'mongoDB'          => Adapter\MongoDb::class,
        'redis'            => Adapter\Redis::class,
        'Redis'            => Adapter\Redis::class,
        'session'          => Adapter\Session::class,
        'Session'          => Adapter\Session::class,
        'xcache'           => Adapter\XCache::class,
        'xCache'           => Adapter\XCache::class,
        'Xcache'           => Adapter\XCache::class,
        'XCache'           => Adapter\XCache::class,
        'win_cache'        => Adapter\WinCache::class,
        'wincache'         => Adapter\WinCache::class,
        'winCache'         => Adapter\WinCache::class,
        'WinCache'         => Adapter\WinCache::class,
        'zend_server_disk' => Adapter\ZendServerDisk::class,
        'zendserverdisk'   => Adapter\ZendServerDisk::class,
        'zendServerDisk'   => Adapter\ZendServerDisk::class,
        'ZendServerDisk'   => Adapter\ZendServerDisk::class,
        'zend_server_shm'  => Adapter\ZendServerShm::class,
        'zendservershm'    => Adapter\ZendServerShm::class,
        'zendServerShm'    => Adapter\ZendServerShm::class,
        'zendServerSHM'    => Adapter\ZendServerShm::class,
        'ZendServerShm'    => Adapter\ZendServerShm::class,
        'ZendServerSHM'    => Adapter\ZendServerShm::class,

        // Legacy Zend Framework aliases
        Apc::class            => Adapter\Apc::class,
        Apcu::class           => Adapter\Apcu::class,
        BlackHole::class      => Adapter\BlackHole::class,
        Dba::class            => Adapter\Dba::class,
        ExtMongoDb::class     => Adapter\ExtMongoDb::class,
        Filesystem::class     => Adapter\Filesystem::class,
        Memcache::class       => Adapter\Memcache::class,
        Memcached::class      => Adapter\Memcached::class,
        Memory::class         => Adapter\Memory::class,
        MongoDb::class        => Adapter\MongoDb::class,
        Redis::class          => Adapter\Redis::class,
        Session::class        => Adapter\Session::class,
        WinCache::class       => Adapter\WinCache::class,
        XCache::class         => Adapter\XCache::class,
        ZendServerDisk::class => Adapter\ZendServerDisk::class,
        ZendServerShm::class  => Adapter\ZendServerShm::class,
    ];

    /** @var array<string,string> */
    protected $factories = [
        Adapter\Apc::class            => InvokableFactory::class,
        Adapter\Apcu::class           => InvokableFactory::class,
        Adapter\BlackHole::class      => InvokableFactory::class,
        Adapter\Dba::class            => InvokableFactory::class,
        Adapter\ExtMongoDb::class     => InvokableFactory::class,
        Adapter\Filesystem::class     => InvokableFactory::class,
        Adapter\Memcache::class       => InvokableFactory::class,
        Adapter\Memcached::class      => InvokableFactory::class,
        Adapter\Memory::class         => InvokableFactory::class,
        Adapter\MongoDb::class        => InvokableFactory::class,
        Adapter\Redis::class          => InvokableFactory::class,
        Adapter\Session::class        => InvokableFactory::class,
        Adapter\WinCache::class       => InvokableFactory::class,
        Adapter\XCache::class         => InvokableFactory::class,
        Adapter\ZendServerDisk::class => InvokableFactory::class,
        Adapter\ZendServerShm::class  => InvokableFactory::class,
    ];

    /**
     * Do not share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /** @var string */
    protected $instanceOf = StorageInterface::class;
}
