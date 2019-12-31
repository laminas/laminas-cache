<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\StorageFactory;

class MemoryIntegrationTest extends SimpleCacheTest
{
    public function setUp()
    {
        $this->skippedTests['testSetTtl'] = 'Memory adapter does not honor TTL';
        $this->skippedTests['testSetMultipleTtl'] = 'Memory adapter does not honor TTL';

        $this->skippedTests['testObjectDoesNotChangeInCache'] =
            'Memory adapter stores objects in memory; so change in references is possible';

        parent::setUp();
    }

    public function createSimpleCache()
    {
        $storage = StorageFactory::adapterFactory('memory');
        return new SimpleCacheDecorator($storage);
    }
}
