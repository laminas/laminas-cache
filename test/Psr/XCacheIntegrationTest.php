<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Cache\Psr;

use Cache\IntegrationTests\CachePoolTest;
use Zend\Cache\Exception\ExtensionNotLoadedException;
use Zend\Cache\Psr\CacheItemPoolAdapter;
use Zend\Cache\Storage\Adapter\XCache;
use Zend\Cache\StorageFactory;

/**
 * @requires extension xcache
 */
class XCacheIntegrationTest extends CachePoolTest
{
    private $tz;

    /**
     * @var XCache
     */
    private $storage;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_XCACHE_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_XCACHE_ENABLED to run this test');
        }

        if ((int)ini_get('xcache.var_size') <= 0) {
            $this->markTestSkipped("ext/xcache is disabled - see 'xcache.var_size'");
        }

        if (PHP_SAPI == 'cli') {
            // this will throw exception for xcache < 3.1.0
            try {
                new XCache();
            } catch (ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function createCachePool()
    {
        $options = [
            'admin_auth' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_AUTH') ? : false,
            'admin_user' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_USER') ? : '',
            'admin_pass' => getenv('TESTS_ZEND_CACHE_XCACHE_ADMIN_PASS') ? : '',
        ];

        $this->storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'xcache',
                'options' => $options,
            ],
        ]);
        return new CacheItemPoolAdapter($this->storage);
    }
}
