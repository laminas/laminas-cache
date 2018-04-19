<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Zend\Cache\Psr\SimpleCacheDecorator;
use Zend\Cache\StorageFactory;

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
