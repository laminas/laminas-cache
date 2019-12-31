<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPUnit\Framework\TestCase;

/**
 * @requires extension xcache
 */
class XCacheIntegrationTest extends TestCase
{

    /**
     * XCache is using request time based TTL handling which violates PSR-6
     *
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testAdapterNotSupported()
    {
        try {
            $storage = StorageFactory::adapterFactory('xcache', [
                'admin_auth' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_AUTH') ?: false,
                'admin_user' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_USER') ?: '',
                'admin_pass' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_PASS') ?: '',
            ]);
            $storage->addPlugin(new Serializer());

            $deferredSkippedMessage = sprintf(
                '%s storage doesn\'t support driver deferred',
                \get_class($storage)
            );
            $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = $deferredSkippedMessage;

            return new CacheItemPoolDecorator($storage);
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
