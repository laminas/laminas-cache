<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

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
        'clearexpiredbyfactor' => Plugin\ClearExpiredByFactor::class,
        'ClearExpiredByFactor' => Plugin\ClearExpiredByFactor::class,
        'exceptionhandler'     => Plugin\ExceptionHandler::class,
        'ExceptionHandler'     => Plugin\ExceptionHandler::class,
        'ignoreuserabort'      => Plugin\IgnoreUserAbort::class,
        'IgnoreUserAbort'      => Plugin\IgnoreUserAbort::class,
        'optimizebyfactor'     => Plugin\OptimizeByFactor::class,
        'OptimizeByFactor'     => Plugin\OptimizeByFactor::class,
        'serializer'           => Plugin\Serializer::class,
        'Serializer'           => Plugin\Serializer::class
    ];

    protected $factories = [
        Plugin\ClearExpiredByFactor::class => InvokableFactory::class,
        Plugin\ExceptionHandler::class     => InvokableFactory::class,
        Plugin\IgnoreUserAbort::class      => InvokableFactory::class,
        Plugin\OptimizeByFactor::class     => InvokableFactory::class,
        Plugin\Serializer::class           => InvokableFactory::class
    ];

    /**
     * Do not share by default
     *
     * @var array
     */
    protected $sharedByDefault = false;

    /**
     * @var string
     */
    protected $instanceOf = Plugin\PluginInterface::class;
}
