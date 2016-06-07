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

class SessionIntegrationTest extends TestCase
{
    /**
     * The session adapter doesn't support TTL
     *
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testAdapterNotSupported()
    {
        $storage = StorageFactory::adapterfactory('session');
        new CacheItemPoolAdapter($storage);
    }
}
