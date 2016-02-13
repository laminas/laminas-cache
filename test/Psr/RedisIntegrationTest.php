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
use Zend\Cache\Storage\Adapter\Redis;
use Zend\Cache\StorageFactory;

class RedisIntegrationTest extends CachePoolTest
{
    private $tz;

    /**
     * @var Redis
     */
    private $storage;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_REDIS_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_REDIS_ENABLED to run this test');
        }

        if (!extension_loaded('redis')) {
            $this->markTestSkipped("Redis extension is not loaded");
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
        $options = ['resource_id' => __CLASS__];

        if (getenv('TESTS_ZEND_CACHE_REDIS_HOST') && getenv('TESTS_ZEND_CACHE_REDIS_PORT')) {
            $options['server'] = [getenv('TESTS_ZEND_CACHE_REDIS_HOST'), getenv('TESTS_ZEND_CACHE_REDIS_PORT')];
        } elseif (getenv('TESTS_ZEND_CACHE_REDIS_HOST')) {
            $options['server'] = [getenv('TESTS_ZEND_CACHE_REDIS_HOST')];
        }

        if (getenv('TESTS_ZEND_CACHE_REDIS_DATABASE')) {
            $options['database'] = getenv('TESTS_ZEND_CACHE_REDIS_DATABASE');
        }

        if (getenv('TESTS_ZEND_CACHE_REDIS_PASSWORD')) {
            $options['password'] = getenv('TESTS_ZEND_CACHE_REDIS_PASSWORD');
        }

        $this->storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'redis',
                'options' => $options,
            ],
        ]);
        return new CacheItemPoolAdapter($this->storage);
    }
}
