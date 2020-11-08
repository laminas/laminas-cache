<?php
declare(strict_types=1);

namespace Laminas\Cache;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Webmozart\Assert\Assert;

class PatternPluginManager extends AbstractPluginManager
{
    /**
     * @var bool
     */
    protected $sharedByDefault = false;

    protected $aliases = [
        'callback' => Pattern\CallbackCache::class,
        'Callback' => Pattern\CallbackCache::class,
        'capture' => Pattern\CaptureCache::class,
        'Capture' => Pattern\CaptureCache::class,
        'class' => Pattern\ClassCache::class,
        'Class' => Pattern\ClassCache::class,
        'object' => Pattern\ObjectCache::class,
        'Object' => Pattern\ObjectCache::class,
        'output' => Pattern\OutputCache::class,
        'Output' => Pattern\OutputCache::class,

        // Legacy Zend Framework aliases
        \Zend\Cache\Pattern\CallbackCache::class => Pattern\CallbackCache::class,
        \Zend\Cache\Pattern\CaptureCache::class => Pattern\CaptureCache::class,
        \Zend\Cache\Pattern\ClassCache::class => Pattern\ClassCache::class,
        \Zend\Cache\Pattern\ObjectCache::class => Pattern\ObjectCache::class,
        \Zend\Cache\Pattern\OutputCache::class => Pattern\OutputCache::class,
    ];

    protected $factories = [
        Pattern\CallbackCache::class => InvokableFactory::class,
        Pattern\CaptureCache::class => InvokableFactory::class,
        Pattern\ClassCache::class => InvokableFactory::class,
        Pattern\ObjectCache::class => InvokableFactory::class,
        Pattern\OutputCache::class => InvokableFactory::class,
    ];

    /**
     * @var string
     */
    protected $instanceOf = Pattern\PatternInterface::class;

    public function build($plugin, array $options = null)
    {
        $pluginInstance = parent::build($plugin, $options);
        if (empty($options)) {
            return $pluginInstance;
        }

        $this->validate($pluginInstance);
        \assert($pluginInstance instanceof Pattern\PatternInterface);
        Assert::isMap($options);

        $pluginInstance->setOptions(new Pattern\PatternOptions($options));

        return $pluginInstance;
    }
}
