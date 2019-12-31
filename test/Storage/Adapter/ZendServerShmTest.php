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
 * @covers Laminas\Cache\Storage\Adapter\ZendServerShm<extended>
 */
class ZendServerShmTest extends CommonAdapterTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED to run this test');
        }

        if (strtolower(PHP_SAPI) == 'cli') {
            $this->markTestSkipped('Zend Server SHM does not work in CLI environment');
            return;
        }

        if (! function_exists('zend_shm_cache_store')) {
            try {
                new Cache\Storage\Adapter\ZendServerShm();
                $this->fail("Missing expected ExtensionNotLoadedException");
            } catch (Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        $this->_options = new Cache\Storage\Adapter\AdapterOptions();
        $this->_storage = new Cache\Storage\Adapter\ZendServerShm($this->_options);
        parent::setUp();
    }

    public function tearDown()
    {
        if (function_exists('zend_shm_cache_clear')) {
            zend_shm_cache_clear();
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['zend_server_shm'],
            ['zendservershm'],
            ['ZendServerShm'],
            ['ZendServerSHM'],
            ['zendServerShm'],
            ['zendServerSHM'],
        ];
    }
}
