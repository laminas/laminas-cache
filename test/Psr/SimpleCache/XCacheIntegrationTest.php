<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * @requires extension xcache
 */
class XCacheIntegrationTest extends SimpleCacheTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_XCACHE_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_XCACHE_ENABLED  to run this test');
        }

        if (! extension_loaded('xcache')) {
            try {
                new Cache\Storage\Adapter\XCache();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        if (PHP_SAPI == 'cli' && version_compare(phpversion('xcache'), '3.1.0') < 0) {
            try {
                new Cache\Storage\Adapter\XCache();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        if ((int)ini_get('xcache.var_size') <= 0) {
            try {
                new Cache\Storage\Adapter\XCache();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        $this->skippedTests['testSetTtl'] = 'XCache adapter does not honor TTL';
        $this->skippedTests['testSetMultipleTtl'] = 'XCache adapter does not honor TTL';

        parent::setUp();
    }

    public function createSimpleCache()
    {
        try {
            $storage = StorageFactory::adapterFactory('xcache', [
                'admin_auth' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_AUTH') ?: false,
                'admin_user' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_USER') ?: '',
                'admin_pass' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_PASS') ?: '',
            ]);
            $storage->addPlugin(new Serializer());
            return new SimpleCacheDecorator($storage);
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof Exception\ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
