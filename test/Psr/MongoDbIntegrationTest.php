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
use Zend\Cache\Storage\Adapter\MongoDb;
use Zend\Cache\StorageFactory;

class MongoDbIntegrationTest extends CachePoolTest
{
    private $tz;

    /**
     * @var MongoDb
     */
    private $storage;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_MONGODB_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_MONGODB_ENABLED to run this test');
        }

        if (!extension_loaded('mongo') || !class_exists('\Mongo') || !class_exists('\MongoClient')) {
            // Allow tests to run if Mongo extension is loaded, or we have a polyfill in place
            $this->markTestSkipped("Mongo extension is not loaded");
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
            'server'     => getenv('TESTS_ZEND_CACHE_MONGODB_CONNECTSTRING'),
            'database'   => getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE'),
            'collection' => getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION'),
        ];
        $this->storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'mongodb',
                'options' => $options,
            ],
        ]);

        return new CacheItemPoolAdapter($this->storage);
    }
}
