<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\ExtMongoDb;
use Laminas\Cache\Storage\Adapter\ExtMongoDbOptions;
use MongoDB\Client;

/**
 * @covers Laminas\Cache\Storage\Adapter\ExtMongoDb<extended>
 */
class ExtMongoDbTest extends CommonAdapterTest
{
    public function setUp()
    {
        if (getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CACHE_MONGODB_ENABLED to run this test');
        }

        if (! extension_loaded('mongodb') || ! class_exists(Client::class)) {
            $this->markTestSkipped("mongodb extension is not loaded");
        }

        $this->_options = new ExtMongoDbOptions([
            'server'     => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_CONNECTSTRING'),
            'database'   => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_DATABASE'),
            'collection' => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_COLLECTION'),
        ]);

        $this->_storage = new ExtMongoDb();
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
            ['ext_mongo_db'],
            ['extmongodb'],
            ['extMongoDb'],
            ['extMongoDB'],
            ['ExtMongoDb'],
            ['ExtMongoDB'],
        ];
    }

    public function testSetOptionsNotMongoDbOptions()
    {
        $this->_storage->setOptions([
            'server'     => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_CONNECTSTRING'),
            'database'   => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_DATABASE'),
            'collection' => getenv('TESTS_LAMINAS_CACHE_EXTMONGODB_COLLECTION'),
        ]);

        $this->assertInstanceOf(ExtMongoDbOptions::class, $this->_storage->getOptions());
    }
}
