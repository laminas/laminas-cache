<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\MongoDb;
use Laminas\Cache\Storage\Adapter\MongoDbOptions;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\MongoDb<extended>
 */
class MongoDbTest extends CommonAdapterTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_MONGODB_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_MONGODB_ENABLED to run this test');
        }

        if (! extension_loaded('mongo') || ! class_exists('\Mongo') || ! class_exists('\MongoClient')) {
            // Allow tests to run if Mongo extension is loaded, or we have a polyfill in place
            $this->markTestSkipped("Mongo extension is not loaded");
        }

        $this->_options = new MongoDbOptions([
            'server'     => getenv('TESTS_LAMINAS_CACHE_MONGODB_CONNECTSTRING'),
            'database'   => getenv('TESTS_LAMINAS_CACHE_MONGODB_DATABASE'),
            'collection' => getenv('TESTS_LAMINAS_CACHE_MONGODB_COLLECTION'),
        ]);

        $this->_storage = new MongoDb();
        $this->_storage->setOptions($this->_options);
        $this->_storage->flush();

        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_storage) {
            $this->_storage->flush();
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['mongo_db'],
            ['mongodb'],
            ['MongoDb'],
            ['MongoDB'],
        ];
    }

    public function testSetOptionsNotMongoDbOptions()
    {
        $this->_storage->setOptions([
            'server'     => getenv('TESTS_LAMINAS_CACHE_MONGODB_CONNECTSTRING'),
            'database'   => getenv('TESTS_LAMINAS_CACHE_MONGODB_DATABASE'),
            'collection' => getenv('TESTS_LAMINAS_CACHE_MONGODB_COLLECTION'),
        ]);

        $this->assertInstanceOf('\Laminas\Cache\Storage\Adapter\MongoDbOptions', $this->_storage->getOptions());
    }
}
