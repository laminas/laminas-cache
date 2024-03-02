<?php

namespace Laminas\Cache\Storage;

use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function array_replace_recursive;

/**
 * Plugin manager implementation for cache plugins
 *
 * Enforces that plugins retrieved are instances of
 * Plugin\PluginInterface. Additionally, it registers a number of default
 * plugins available.
 *
 * @extends AbstractSingleInstancePluginManager<PluginInterface>
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class PluginManager extends AbstractSingleInstancePluginManager
{
    private const CONFIGURATION = [
        'aliases'   => [
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
        ],
        'factories' => [
            Plugin\ClearExpiredByFactor::class => InvokableFactory::class,
            Plugin\ExceptionHandler::class     => InvokableFactory::class,
            Plugin\IgnoreUserAbort::class      => InvokableFactory::class,
            Plugin\OptimizeByFactor::class     => InvokableFactory::class,
            Plugin\Serializer::class           => Plugin\SerializerFactory::class,
        ],
    ];

    protected bool $sharedByDefault = false;

    public function __construct(ContainerInterface $creationContext, array $config = [])
    {
        $this->instanceOf = PluginInterface::class;
        /** @var ServiceManagerConfiguration $config */
        $config = array_replace_recursive(self::CONFIGURATION, $config);
        parent::__construct($creationContext, $config);
    }

    public function build(string $name, ?array $options = null): PluginInterface
    {
        $options ??= [];
        $plugin    = parent::build($name);
        if ($options !== []) {
            Assert::isMap($options);
            $plugin->setOptions(new PluginOptions($options));
        }

        return $plugin;
    }
}
