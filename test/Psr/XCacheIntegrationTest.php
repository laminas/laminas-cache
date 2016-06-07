<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache\Psr;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Cache\Psr\CacheItemPoolAdapter;
use Zend\Cache\StorageFactory;
use Zend\Cache\Exception;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * @requires extension xcache
 */
class XCacheIntegrationTest extends TestCase
{

    /**
     * XCache is using request time based TTL handling which violates PSR-6
     *
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testAdapterNotSupported()
    {
        try {
            $storage = StorageFactory::adapterFactory('xcache', [
                'admin_auth' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_AUTH') ?: false,
                'admin_user' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_USER') ?: '',
                'admin_pass' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_PASS') ?: '',
            ]);
            return new CacheItemPoolAdapter($storage);
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
