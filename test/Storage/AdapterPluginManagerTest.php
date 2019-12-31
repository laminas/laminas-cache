<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Exception\ExtensionNotLoadedException;
use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit_Framework_TestCase as TestCase;

class AdapterPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait {
        testPluginAliasesResolve as commonPluginAliasesResolve;
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testPluginAliasesResolve($alias, $expected)
    {
        try {
            $this->commonPluginAliasesResolve($alias, $expected);
        } catch (ServiceNotCreatedException $e) {
            // if we get as far as "extension not loaded" we've hit the constructor: alias has resolved
            if (! $e->getPrevious() instanceof ExtensionNotLoadedException) {
                $this->fail($e->getMessage());
            }
        }
    }

    protected function getPluginManager()
    {
        return new AdapterPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return StorageInterface::class;
    }
}
