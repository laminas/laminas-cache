<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\RedisResourceManager;

/**
 * PHPUnit test case
 */

/**
 * @group      Laminas_Cache
 */
class RedisResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The resource manager
     *
     * @var RedisResourceManager
     */
    protected $resourceManager;

    public function setUp()
    {
        $this->resourceManager = new RedisResourceManager();
    }

    /**
     * Test with 'persistent_id'
     */
    public function testValidPersistentId()
    {
        $resourceId = 'testValidPersistentId';
        $resource   = array(
            'persistent_id' => 1234,
            'server' => array(
                'host' => 'localhost'
            ),
        );
        $expectedPersistentId = '1234';
        $this->resourceManager->setResource($resourceId, $resource);
        $this->assertSame($expectedPersistentId, $this->resourceManager->getPersistentId($resourceId));
    }

    /**
     * Test with 'persistend_id'
     */
    public function testNotValidPersistentId()
    {
        $resourceId = 'testNotValidPersistentId';
        $resource   = array(
            'persistend_id' => 1234,
            'server' => array(
                'host' => 'localhost'
            ),
        );
        $expectedPersistentId = '1234';
        $this->resourceManager->setResource($resourceId, $resource);

        $this->assertNotSame($expectedPersistentId, $this->resourceManager->getPersistentId($resourceId));
        $this->assertEmpty($this->resourceManager->getPersistentId($resourceId));
    }
}
