<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage;

use Laminas\Cache\Exception;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for cache plugins
 *
 * Enforces that plugins retrieved are instances of
 * Plugin\PluginInterface. Additionally, it registers a number of default
 * plugins available.
 */
class PluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'clear_expired_by_factor' => Plugin\ClearExpiredByFactor::class,
        'clearexpiredbyfactor'    => Plugin\ClearExpiredByFactor::class,
        'clearExpiredByFactor'    => Plugin\ClearExpiredByFactor::class,
        'ClearExpiredByFactor'    => Plugin\ClearExpiredByFactor::class,
        'exception_handler'       => Plugin\ExceptionHandler::class,
        'exceptionhandler'        => Plugin\ExceptionHandler::class,
        'exceptionHandler'        => Plugin\ExceptionHandler::class,
        'ExceptionHandler'        => Plugin\ExceptionHandler::class,
        'ignore_user_abort'       => Plugin\IgnoreUserAbort::class,
        'ignoreuserabort'         => Plugin\IgnoreUserAbort::class,
        'ignoreUserAbort'         => Plugin\IgnoreUserAbort::class,
        'IgnoreUserAbort'         => Plugin\IgnoreUserAbort::class,
        'optimize_by_factor'      => Plugin\OptimizeByFactor::class,
        'optimizebyfactor'        => Plugin\OptimizeByFactor::class,
        'optimizeByFactor'        => Plugin\OptimizeByFactor::class,
        'OptimizeByFactor'        => Plugin\OptimizeByFactor::class,
        'serializer'              => Plugin\Serializer::class,
        'Serializer'              => Plugin\Serializer::class,

        // Legacy Zend Framework aliases
        \Zend\Cache\Storage\Plugin\ClearExpiredByFactor::class => Plugin\ClearExpiredByFactor::class,
        \Zend\Cache\Storage\Plugin\ExceptionHandler::class => Plugin\ExceptionHandler::class,
        \Zend\Cache\Storage\Plugin\IgnoreUserAbort::class => Plugin\IgnoreUserAbort::class,
        \Zend\Cache\Storage\Plugin\OptimizeByFactor::class => Plugin\OptimizeByFactor::class,
        \Zend\Cache\Storage\Plugin\Serializer::class => Plugin\Serializer::class,
    ];

    protected $factories = [
        Plugin\ClearExpiredByFactor::class           => InvokableFactory::class,
        Plugin\ExceptionHandler::class               => InvokableFactory::class,
        Plugin\IgnoreUserAbort::class                => InvokableFactory::class,
        Plugin\OptimizeByFactor::class               => InvokableFactory::class,
        Plugin\Serializer::class                     => InvokableFactory::class,
    ];

    /**
     * Do not share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /**
     * @var string
     */
    protected $instanceOf = Plugin\PluginInterface::class;
}
