<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\WinCache<extended>
 */
class WinCacheTest extends CommonAdapterTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_WINCACHE_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_WINCACHE_ENABLED to run this test');
        }

        if (! extension_loaded('wincache')) {
            $this->markTestSkipped("WinCache extension is not loaded");
        }

        $enabled = ini_get('wincache.ucenabled');
        if (PHP_SAPI == 'cli') {
            $enabled = $enabled && (bool) ini_get('wincache.enablecli');
        }

        if (! $enabled) {
            throw new Exception\ExtensionNotLoadedException(
                "WinCache is disabled - see 'wincache.ucenabled' and 'wincache.enablecli'"
            );
        }

        $this->_options = new Cache\Storage\Adapter\WinCacheOptions();
        $this->_storage = new Cache\Storage\Adapter\WinCache();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown()
    {
        if (function_exists('wincache_ucache_clear')) {
            wincache_ucache_clear();
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['win_cache'],
            ['wincache'],
            ['WinCache'],
            ['winCache'],
        ];
    }
}
