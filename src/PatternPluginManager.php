<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Cache;

use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Webmozart\Assert\Assert;
use Zend\Cache\Pattern\CallbackCache;
use Zend\Cache\Pattern\CaptureCache;
use Zend\Cache\Pattern\ClassCache;
use Zend\Cache\Pattern\ObjectCache;
use Zend\Cache\Pattern\OutputCache;

use function assert;

class PatternPluginManager extends AbstractPluginManager
{
    /** @var bool */
    protected $sharedByDefault = false;

    /** @var array<string,string> */
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
        CallbackCache::class => Pattern\CallbackCache::class,
        CaptureCache::class  => Pattern\CaptureCache::class,
        ClassCache::class    => Pattern\ClassCache::class,
        ObjectCache::class   => Pattern\ObjectCache::class,
        OutputCache::class   => Pattern\OutputCache::class,
    ];

    /** @var array<string,string> */
    protected $factories = [
        Pattern\CallbackCache::class => InvokableFactory::class,
        Pattern\CaptureCache::class  => InvokableFactory::class,
        Pattern\ClassCache::class    => InvokableFactory::class,
        Pattern\ObjectCache::class   => InvokableFactory::class,
        Pattern\OutputCache::class   => InvokableFactory::class,
    ];

    /** @var string */
    protected $instanceOf = Pattern\PatternInterface::class;

    /**
     * @param string $plugin
     * @param array<string,mixed>|null $options
     * @return Pattern\PatternInterface
     * @throws ContainerException
     */
    public function build($plugin, ?array $options = null)
    {
        $pluginInstance = parent::build($plugin, $options);
        if (empty($options)) {
            return $pluginInstance;
        }

        $this->validate($pluginInstance);
        assert($pluginInstance instanceof Pattern\PatternInterface);
        Assert::isMap($options);

        $pluginInstance->setOptions(new Pattern\PatternOptions($options));

        return $pluginInstance;
    }
}
