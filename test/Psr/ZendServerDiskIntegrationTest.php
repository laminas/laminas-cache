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
use Zend\Cache\StorageFactory;

class ZendServerDiskIntegrationTest extends CachePoolTest
{
    private $tz;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_ZEND_SERVER_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_ZEND_SERVER_ENABLED to run this test');
        }

        if (!function_exists('zend_disk_cache_store') || PHP_SAPI == 'cli') {
            $this->markTestSkipped("Missing 'zend_disk_cache_*' functions or running from SAPI 'cli'");
        }

        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if (function_exists('zend_disk_cache_clear')) {
            zend_disk_cache_clear();
        }

        parent::tearDown();
    }

    public function createCachePool()
    {
        $storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'xcache',
                'options' => [],
            ],
        ]);
        return new CacheItemPoolAdapter($storage);
    }
}
