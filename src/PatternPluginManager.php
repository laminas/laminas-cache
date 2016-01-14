<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Exception\InvalidServiceException;

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
     * @var boolean
     */
    protected $shareByDefault = false;

    /**
     * Don't share by default
     *
     * @var boolean
     */
    protected $sharedByDefault = false;

    /**
     * @var string
     */
    protected $instanceOf = Pattern\PatternInterface::class;

    /**
     * Validate the plugin is of the expected type (v3).
     *
     * Validates against `$instanceOf`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validate($instance)
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                '%s can only create instances of %s; %s is invalid',
                get_class($this),
                $this->instanceOf,
                (is_object($instance) ? get_class($instance) : gettype($instance))
            ));
        }
    }

    /**
     * Validate the plugin is of the expected type (v2).
     *
     * Proxies to `validate()`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validatePlugin($instance)
    {
        $this->validate($instance);
    }
}
