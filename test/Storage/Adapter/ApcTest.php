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
class ApcTest extends CommonAdapterTest
{

    /**
     * Restore 'apc.use_request_time'
     *
     * @var mixed
     */
    protected $iniUseRequestTime;

    public function setUp()
    {
        if (!defined('TESTS_LAMINAS_CACHE_APC_ENABLED') || !TESTS_LAMINAS_CACHE_APC_ENABLED) {
            $this->markTestSkipped("Skipped by TestConfiguration (TESTS_LAMINAS_CACHE_APC_ENABLED)");
        }

        if (version_compare('3.1.6', phpversion('apc')) > 0) {
            try {
                new Cache\Storage\Adapter\Apc();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped("Missing ext/apc >= 3.1.6");
            }
        }

        $enabled = (bool) ini_get('apc.enabled');
        if (PHP_SAPI == 'cli') {
            $enabled = $enabled && (bool) ini_get('apc.enable_cli');
        }

        if (!$enabled) {
            try {
                new Cache\Storage\Adapter\Apc();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped("ext/apc is disabled - see 'apc.enabled' and 'apc.enable_cli'");
            }
        }

        // needed on test expirations
        $this->iniUseRequestTime = ini_get('apc.use_request_time');
        ini_set('apc.use_request_time', 0);

        $this->_options = new Cache\Storage\Adapter\ApcOptions();
        $this->_storage = new Cache\Storage\Adapter\Apc();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        }

        // reset ini configurations
        ini_set('apc.use_request_time', $this->iniUseRequestTime);

        parent::tearDown();
    }
}
