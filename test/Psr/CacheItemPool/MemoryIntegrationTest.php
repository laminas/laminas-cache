<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\StorageFactory;
use PHPUnit\Framework\TestCase;

class MemoryIntegrationTest extends TestCase
{
    /**
     * The memory adapter calculates the TTL on reading which violates PSR-6
     *
     * @expectedException \Laminas\Cache\Psr\CacheItemPool\CacheException
     */
    public function testAdapterNotSupported()
    {
        $storage = StorageFactory::adapterFactory('memory');
        new CacheItemPoolDecorator($storage);
    }
}
