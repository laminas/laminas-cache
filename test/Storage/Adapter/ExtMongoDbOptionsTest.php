<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\ExtMongoDbOptions;
use Laminas\Cache\Storage\Adapter\ExtMongoDbResourceManager;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;

/**
 * @covers Laminas\Cache\Storage\Adapter\ExtMongoDbOptions<extended>
 */
class ExtMongoDbOptionsTest extends TestCase
{
    protected $object;

    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_EXTMONGODB_ENABLED to run this test');
        }

        if (! extension_loaded('mongodb') || ! class_exists(Client::class)) {
            $this->markTestSkipped("mongodb extension is not loaded");
        }

        $this->object = new ExtMongoDbOptions();
    }

    public function testSetNamespaceSeparator()
    {
        $this->assertAttributeEquals(':', 'namespaceSeparator', $this->object);

        $namespaceSeparator = '_';

        $this->object->setNamespaceSeparator($namespaceSeparator);

        $this->assertAttributeEquals($namespaceSeparator, 'namespaceSeparator', $this->object);
    }

    public function testGetNamespaceSeparator()
    {
        $this->assertEquals(':', $this->object->getNamespaceSeparator());

        $namespaceSeparator = '_';

        $this->object->setNamespaceSeparator($namespaceSeparator);

        $this->assertEquals($namespaceSeparator, $this->object->getNamespaceSeparator());
    }

    public function testSetResourceManager()
    {
        $this->assertAttributeEquals(null, 'resourceManager', $this->object);

        $resourceManager = new ExtMongoDbResourceManager();

        $this->object->setResourceManager($resourceManager);

        $this->assertAttributeSame($resourceManager, 'resourceManager', $this->object);
    }

    public function testGetResourceManager()
    {
        $this->assertInstanceOf(
            ExtMongoDbResourceManager::class,
            $this->object->getResourceManager()
        );

        $resourceManager = new ExtMongoDbResourceManager();

        $this->object->setResourceManager($resourceManager);

        $this->assertSame($resourceManager, $this->object->getResourceManager());
    }

    public function testSetResourceId()
    {
        $this->assertAttributeEquals('default', 'resourceId', $this->object);

        $resourceId = 'foo';

        $this->object->setResourceId($resourceId);

        $this->assertAttributeEquals($resourceId, 'resourceId', $this->object);
    }

    public function testGetResourceId()
    {
        $this->assertEquals('default', $this->object->getResourceId());

        $resourceId = 'foo';

        $this->object->setResourceId($resourceId);

        $this->assertEquals($resourceId, $this->object->getResourceId());
    }

    public function testSetServer()
    {
        $resourceManager = new ExtMongoDbResourceManager();
        $this->object->setResourceManager($resourceManager);

        $resourceId = $this->object->getResourceId();
        $server     = 'mongodb://test:1234';

        $this->assertFalse($this->object->getResourceManager()->hasResource($resourceId));

        $this->object->setServer($server);
        $this->assertSame($server, $this->object->getResourceManager()->getServer($resourceId));
    }
}
