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
 * @covers Laminas\Cache\Storage\Adapter\ZendServerDisk<extended>
 */
class ZendServerDiskTest extends CommonAdapterTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED to run this test');
        }

        if (! function_exists('zend_disk_cache_store') || PHP_SAPI == 'cli') {
            try {
                new Cache\Storage\Adapter\ZendServerDisk();
                $this->fail("Missing expected ExtensionNotLoadedException");
            } catch (Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        $this->_options = new Cache\Storage\Adapter\AdapterOptions();
        $this->_storage = new Cache\Storage\Adapter\ZendServerDisk($this->_options);
        parent::setUp();
    }

    public function tearDown()
    {
        if (function_exists('zend_disk_cache_clear')) {
            zend_disk_cache_clear();
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['zend_server_disk'],
            ['zendserverdisk'],
            ['ZendServerDisk'],
            ['zendServerDisk'],
        ];
    }
}
