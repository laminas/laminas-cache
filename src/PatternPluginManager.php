<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for cache pattern adapters
 *
 * Enforces that retrieved adapters are instances of
 * Pattern\PatternInterface. Additionally, it registers a number of default
 * patterns available.
 */
class PatternPluginManager extends AbstractPluginManager
{
    /**
     * Default set of adapters
     *
     * @var array
     */
    protected $invokableClasses = [
        'callback' => 'Laminas\Cache\Pattern\CallbackCache',
        'capture'  => 'Laminas\Cache\Pattern\CaptureCache',
        'class'    => 'Laminas\Cache\Pattern\ClassCache',
        'object'   => 'Laminas\Cache\Pattern\ObjectCache',
        'output'   => 'Laminas\Cache\Pattern\OutputCache',
        'page'     => 'Laminas\Cache\Pattern\PageCache',
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
