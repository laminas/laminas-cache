<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache\Psr;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Cache\Psr\CacheItemPoolAdapter;
use Zend\Cache\StorageFactory;

class MemoryIntegrationTest extends TestCase
{
    /**
     * The memory adapter calculates the TTL on reading which violates PSR-6
     *
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testAdapterNotSupported()
    {
        $storage = StorageFactory::adapterFactory('memory');
        new CacheItemPoolAdapter($storage);
    }
}
