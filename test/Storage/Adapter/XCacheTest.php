<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\XCache<extended>
 */
class XCacheTest extends CommonAdapterTest
{
    protected $backupServerArray;

    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_XCACHE_ENABLED') != 'true') {
            $this->markTestSkipped('EnableTESTS_LAMINAS_CACHE_XCACHE_ENABLED  to run this test');
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

        $this->_options = new Cache\Storage\Adapter\XCacheOptions([
            'admin_auth' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_AUTH') ? : false,
            'admin_user' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_USER') ? : '',
            'admin_pass' => getenv('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_PASS') ? : '',
        ]);
        $this->_storage = new Cache\Storage\Adapter\XCache();
        $this->_storage->setOptions($this->_options);

        // dfsdfdsfsdf
        $this->backupServerArray = $_SERVER;

        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_storage) {
            $this->_storage->flush();
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['xcache'],
            ['XCache'],
            ['Xcache'],
            ['xCache'],
        ];
    }
}
