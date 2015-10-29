<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for cache pattern adapters
 *
 * Enforces that retrieved adapters are instances of
 * Pattern\PatternInterface. Additionally, it registers a number of default
 * patterns available.
 */
class PatternPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'callback' => Pattern\CallbackCache::class,
        'capture'  => Pattern\CaptureCache::class,
        'class'    => Pattern\ClassCache::class,
        'object'   => Pattern\ObjectCache::class,
        'output'   => Pattern\OutputCache::class,
        'page'     => Pattern\PageCache::class,
    ];

    protected $factories = [
        Pattern\CallbackCache::class => InvokableFactory::class,
        Pattern\CaptureCache::class  => InvokableFactory::class,
        Pattern\ClassCache::class    => InvokableFactory::class,
        Pattern\ObjectCache::class   => InvokableFactory::class,
        Pattern\OutputCache::class   => InvokableFactory::class,
        Pattern\PageCache::class     => InvokableFactory::class,
    ];

    /**
     * Don't share by default
     *
     * @var array
     */
    protected $sharedByDefault = false;

    /**
     * @var string
     */
    protected $instanceOf = Pattern\PatternInterface::class;
}
