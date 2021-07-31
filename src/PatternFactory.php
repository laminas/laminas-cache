<?php

namespace Laminas\Cache;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

/**
 * @deprecated Please do not use static factories anymore. Please create your own instances by creating a
 *             dedicated factory for each of the patterns with your project specific needs or just instantiate
 *             the pattern manually instead of calling this factory.
 */
abstract class PatternFactory
{
    /**
     * The pattern manager
     *
     * @var null|PatternPluginManager
     */
    protected static $plugins;

    /**
     * Instantiate a cache pattern
     *
     * @param  string|Pattern\PatternInterface $patternName
     * @param  array<string,mixed>|Traversable<string,mixed>|Pattern\PatternOptions $options
     * @return Pattern\PatternInterface
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($patternName, $options = [])
    {
        if ($options instanceof Pattern\PatternOptions) {
            $options = $options->toArray();
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array, Traversable object, or %s\Pattern\PatternOptions object; received "%s"',
                __METHOD__,
                __NAMESPACE__,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        if ($patternName instanceof Pattern\PatternInterface) {
            $patternName->setOptions(new Pattern\PatternOptions($options));
            return $patternName;
        }

        return static::getPluginManager()->get($patternName, $options);
    }

    /**
     * Get the pattern plugin manager
     *
     * @return PatternPluginManager
     */
    public static function getPluginManager()
    {
        if (static::$plugins === null) {
            static::$plugins = new PatternPluginManager(new ServiceManager);
        }

        return static::$plugins;
    }

    /**
     * Set the pattern plugin manager
     *
     * @return void
     */
    public static function setPluginManager(PatternPluginManager $plugins)
    {
        static::$plugins = $plugins;
    }

    /**
     * Reset pattern plugin manager to default
     *
     * @return void
     */
    public static function resetPluginManager()
    {
        static::$plugins = null;
    }
}
