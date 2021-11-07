<?php

namespace Laminas\Cache\Storage;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for cache plugins
 *
 * Enforces that plugins retrieved are instances of
 * Plugin\PluginInterface. Additionally, it registers a number of default
 * plugins available.
 */
final class PluginManager extends AbstractPluginManager
{
    /** @var array<string,string> */
    protected $aliases = [
        'clear_expired_by_factor' => Plugin\ClearExpiredByFactor::class,
        'clearexpiredbyfactor'    => Plugin\ClearExpiredByFactor::class,
        'clearExpiredByFactor'    => Plugin\ClearExpiredByFactor::class,
        'ClearExpiredByFactor'    => Plugin\ClearExpiredByFactor::class,
        'exception_handler'       => Plugin\ExceptionHandler::class,
        'exceptionhandler'        => Plugin\ExceptionHandler::class,
        'exceptionHandler'        => Plugin\ExceptionHandler::class,
        'ExceptionHandler'        => Plugin\ExceptionHandler::class,
        'ignore_user_abort'       => Plugin\IgnoreUserAbort::class,
        'ignoreuserabort'         => Plugin\IgnoreUserAbort::class,
        'ignoreUserAbort'         => Plugin\IgnoreUserAbort::class,
        'IgnoreUserAbort'         => Plugin\IgnoreUserAbort::class,
        'optimize_by_factor'      => Plugin\OptimizeByFactor::class,
        'optimizebyfactor'        => Plugin\OptimizeByFactor::class,
        'optimizeByFactor'        => Plugin\OptimizeByFactor::class,
        'OptimizeByFactor'        => Plugin\OptimizeByFactor::class,
        'serializer'              => Plugin\Serializer::class,
        'Serializer'              => Plugin\Serializer::class,
    ];

    /** @var array<string,string> */
    protected $factories = [
        Plugin\ClearExpiredByFactor::class => InvokableFactory::class,
        Plugin\ExceptionHandler::class     => InvokableFactory::class,
        Plugin\IgnoreUserAbort::class      => InvokableFactory::class,
        Plugin\OptimizeByFactor::class     => InvokableFactory::class,
        Plugin\Serializer::class           => InvokableFactory::class,
    ];

    /**
     * Do not share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /** @var string */
    protected $instanceOf = PluginInterface::class;

    /**
     * @param  string $name
     * @param  null|array  $options
     * @return mixed
     */
    public function build($name, ?array $options = null)
    {
        $options = $options ?? [];
        /** @psalm-suppress MixedAssignment */
        $plugin = parent::build($name);
        if ($options !== [] && $plugin instanceof PluginInterface) {
            $plugin->setOptions(new PluginOptions($options));
        }

        return $plugin;
    }
}
