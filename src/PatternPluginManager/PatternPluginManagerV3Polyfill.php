<?php

namespace Laminas\Cache\PatternPluginManager;

use Laminas\Cache\Pattern;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * laminas-servicemanager v3-compatible plugin manager implementation for cache pattern adapters.
 *
 * Enforces that retrieved adapters are instances of
 * Pattern\PatternInterface. Additionally, it registers a number of default
 * patterns available.
 * @deprecated This will be removed in v3.0.0. Cache pattern will require dependency injection and thus, a generic
 *             plugin manager makes no sense anymore.
 */
class PatternPluginManagerV3Polyfill extends AbstractPluginManager
{
    use PatternPluginManagerTrait;

    protected $aliases = [
        'callback' => Pattern\CallbackCache::class,
        'Callback' => Pattern\CallbackCache::class,
        'capture'  => Pattern\CaptureCache::class,
        'Capture'  => Pattern\CaptureCache::class,
        'class'    => Pattern\ClassCache::class,
        'Class'    => Pattern\ClassCache::class,
        'object'   => Pattern\ObjectCache::class,
        'Object'   => Pattern\ObjectCache::class,
        'output'   => Pattern\OutputCache::class,
        'Output'   => Pattern\OutputCache::class,

        // Legacy Zend Framework aliases
        \Zend\Cache\Pattern\CallbackCache::class => Pattern\CallbackCache::class,
        \Zend\Cache\Pattern\CaptureCache::class => Pattern\CaptureCache::class,
        \Zend\Cache\Pattern\ClassCache::class => Pattern\ClassCache::class,
        \Zend\Cache\Pattern\ObjectCache::class => Pattern\ObjectCache::class,
        \Zend\Cache\Pattern\OutputCache::class => Pattern\OutputCache::class,

        // v2 normalized FQCNs
        'zendcachepatterncallbackcache' => Pattern\CallbackCache::class,
        'zendcachepatterncapturecache' => Pattern\CaptureCache::class,
        'zendcachepatternclasscache' => Pattern\ClassCache::class,
        'zendcachepatternobjectcache' => Pattern\ObjectCache::class,
        'zendcachepatternoutputcache' => Pattern\OutputCache::class,
    ];

    protected $factories = [
        Pattern\CallbackCache::class    => Pattern\StoragePatternCacheFactory::class,
        Pattern\CaptureCache::class     => Pattern\PatternCacheFactory::class,
        Pattern\ClassCache::class       => Pattern\StoragePatternCacheFactory::class,
        Pattern\ObjectCache::class      => Pattern\StoragePatternCacheFactory::class,
        Pattern\OutputCache::class      => Pattern\StoragePatternCacheFactory::class,

        // v2 normalized FQCNs
        'laminascachepatterncallbackcache' => Pattern\StoragePatternCacheFactory::class,
        'laminascachepatterncapturecache'  => Pattern\PatternCacheFactory::class,
        'laminascachepatternclasscache'    => Pattern\StoragePatternCacheFactory::class,
        'laminascachepatternobjectcache'   => Pattern\StoragePatternCacheFactory::class,
        'laminascachepatternoutputcache'   => Pattern\StoragePatternCacheFactory::class,
    ];

    /**
     * Don't share by default
     *
     * @var bool
     */
    protected $shareByDefault = false;

    /**
     * Don't share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /**
     * @var string
     */
    protected $instanceOf = Pattern\PatternInterface::class;

    /**
     * Override get to inject options as PatternOptions instance.
     *
     * {@inheritDoc}
     */
    public function get($plugin, array $options = null, $usePeeringServiceManagers = true)
    {
        if (empty($options)) {
            return parent::get($plugin, null, $usePeeringServiceManagers);
        }

        $plugin = parent::get($plugin, null, $usePeeringServiceManagers);
        $plugin->setOptions(new Pattern\PatternOptions($options));
        return $plugin;
    }
}
