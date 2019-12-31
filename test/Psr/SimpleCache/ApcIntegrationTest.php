<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Exception\ExtensionNotLoadedException;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

class ApcIntegrationTest extends SimpleCacheTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    /**
     * Restore 'apc.use_request_time'
     *
     * @var mixed
     */
    protected $iniUseRequestTime;

    protected function setUp()
    {
        if (! getenv('TESTS_LAMINAS_CACHE_APC_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_APC_ENABLED to run this test');
        }

        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        // needed on test expirations
        $this->iniUseRequestTime = ini_get('apc.use_request_time');
        ini_set('apc.use_request_time', 0);

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        }

        // reset ini configurations
        ini_set('apc.use_request_time', $this->iniUseRequestTime);

        parent::tearDown();
    }

    public function createSimpleCache()
    {
        try {
            $storage = StorageFactory::adapterFactory('apc');
            return new SimpleCacheDecorator($storage);
        } catch (ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
