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
 * Plugin manager implementation for cache plugins
 *
 * Enforces that plugins retrieved are instances of
 * Plugin\PluginInterface. Additionally, it registers a number of default
 * plugins available.
 *
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage Storage
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * Default set of plugins
     *
     * @var array
     */
    protected $invokableClasses = array(
        'clearexpiredbyfactor' => 'Laminas\Cache\Storage\Plugin\ClearExpiredByFactor',
        'exceptionhandler'     => 'Laminas\Cache\Storage\Plugin\ExceptionHandler',
        'ignoreuserabort'      => 'Laminas\Cache\Storage\Plugin\IgnoreUserAbort',
        'optimizebyfactor'     => 'Laminas\Cache\Storage\Plugin\OptimizeByFactor',
        'serializer'           => 'Laminas\Cache\Storage\Plugin\Serializer',
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
     * Checks that the plugin loaded is an instance of Plugin\PluginInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof Plugin\PluginInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s\Plugin\PluginInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}
