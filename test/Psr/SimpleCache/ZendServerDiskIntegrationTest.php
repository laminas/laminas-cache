<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

class ZendServerDiskIntegrationTest extends SimpleCacheTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    protected function setUp()
    {
        if (! getenv('TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_ZEND_SERVER_ENABLED to run this test');
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

    public function createSimpleCache()
    {
        try {
            $storage = StorageFactory::adapterFactory('zendserverdisk');
            return new SimpleCacheDecorator($storage);
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof Exception\ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
