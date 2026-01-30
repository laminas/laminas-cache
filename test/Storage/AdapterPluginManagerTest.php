<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

final class AdapterPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait {
        testPluginAliasesResolve as commonPluginAliasesResolve;
    }

    /**
     * This inherited test has been disabled using a bogus PHP version requirement
     *
     * The plugin manager does not specify any aliases at all, so PHPUnit will complain about the empty data provider.
     * We cannot delete the method either and nor can we skip it inside the method due to the method parameter
     * requirements.
     *
     * @psalm-suppress UnusedParam
     */
    #[RequiresPhp('<8.0')]
    public function testPluginAliasesResolve(string $alias, string $expected)
    {
        self::markTestSkipped('There are no aliases to test');
    }

    protected static function getPluginManager(array $config = []): AbstractSingleInstancePluginManager
    {
        return new AdapterPluginManager(new ServiceManager(), $config);
    }

    protected function getInstanceOf(): string
    {
        return StorageInterface::class;
    }
}
