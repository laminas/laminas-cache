<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 */
class XCacheTest extends CommonAdapterTest
{

    protected $backupServerArray;

    public function setUp()
    {
        if (!defined('TESTS_LAMINAS_CACHE_XCACHE_ENABLED') || !TESTS_LAMINAS_CACHE_XCACHE_ENABLED) {
            $this->markTestSkipped("Skipped by TestConfiguration (TESTS_LAMINAS_CACHE_XCACHE_ENABLED)");
        }

        if (!extension_loaded('xcache')) {
            try {
                new Cache\Storage\Adapter\XCache();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }

        if (PHP_SAPI == 'cli') {
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

        $this->_options = new Cache\Storage\Adapter\XCacheOptions(array(
            'admin_auth' => defined('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_AUTH') ? TESTS_LAMINAS_CACHE_XCACHE_ADMIN_AUTH : false,
            'admin_user' => defined('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_USER') ? TESTS_LAMINAS_CACHE_XCACHE_ADMIN_USER : '',
            'admin_pass' => defined('TESTS_LAMINAS_CACHE_XCACHE_ADMIN_PASS') ? TESTS_LAMINAS_CACHE_XCACHE_ADMIN_PASS : '',
        ));
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
}
