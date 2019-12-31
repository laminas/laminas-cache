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
use Laminas\Cache\Storage\Adapter\Memcached;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * @require extension memcached
 */
class MemcachedIntegrationTest extends SimpleCacheTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    /**
     * @var Memcached
     */
    private $storage;

    protected function setUp()
    {
        if (! getenv('TESTS_LAMINAS_CACHE_MEMCACHED_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_MEMCACHED_ENABLED to run this test');
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

    public function createSimpleCache()
    {
        $host = getenv('TESTS_LAMINAS_CACHE_MEMCACHED_HOST');
        $port = getenv('TESTS_LAMINAS_CACHE_MEMCACHED_PORT');

        $options = [
            'resource_id' => __CLASS__
        ];
        if ($host && $port) {
            $options['servers'] = [[$host, $port]];
        } elseif ($host) {
            $options['servers'] = [[$host]];
        }

        try {
            $storage = StorageFactory::adapterFactory('memcached', $options);
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
