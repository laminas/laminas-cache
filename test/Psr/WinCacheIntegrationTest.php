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
use Zend\Cache\Storage\Adapter\WinCache;
use Zend\Cache\StorageFactory;

/**
 * @requires extension wincache
 */
class WinCacheIntegrationTest extends CachePoolTest
{
    private $tz;

    /**
     * @var WinCache
     */
    private $storage;

    protected function setUp()
    {
        if (!getenv('TESTS_ZEND_CACHE_WINCACHE_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_WINCACHE_ENABLED to run this test');
        }

        $enabled = ini_get('wincache.ucenabled');
        if (PHP_SAPI == 'cli') {
            $enabled = $enabled && (bool) ini_get('wincache.enablecli');
        }

        if (!$enabled) {
            $this->markTestSkipped("WinCache is disabled - see 'wincache.ucenabled' and 'wincache.enablecli'");
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
        $this->storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'xcache',
                'options' => [],
            ],
        ]);
        return new CacheItemPoolAdapter($this->storage);
    }
}
