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
use Zend\Cache\Psr\CacheItemPoolAdapter;
use Zend\Cache\Storage\Adapter\Memcache;
use Zend\Cache\StorageFactory;

/**
 * @require extension memcache
 */
class MemcacheIntegrationTest extends CachePoolTest
{
    private $tz;

    /**
     * @var Memcache
     */
    private $storage;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_MEMCACHE_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_MEMCACHE_ENABLED to run this test');
        }

        if (version_compare('2.0.0', phpversion('memcache')) > 0) {
            $this->markTestSkipped("Missing ext/memcache version >= 2.0.0");
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
        $host = getenv('TESTS_ZEND_CACHE_MEMCACHE_HOST');
        $port = getenv('TESTS_ZEND_CACHE_MEMCACHE_PORT');

        $options = [
            'resource_id' => __CLASS__
        ];
        if ($host && $port) {
            $options['servers'] = [[$host, $port]];
        } elseif ($host) {
            $options['servers'] = [[$host]];
        }

        $adapter = StorageFactory::factory([
            'adapter' => [
                'name'    => 'memcache',
                'options' => $options,
            ],
        ]);
        return new CacheItemPoolAdapter($adapter);
    }
}
