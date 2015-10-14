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
    protected $shareByDefault = false;

    /**
     * Validate the plugin
     *
     * Checks that the pattern adapter loaded is an instance of Pattern\PatternInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof Pattern\PatternInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s\Pattern\PatternInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}
