<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Memory<extended>
 */
class MemoryTest extends CommonAdapterTest
{
    public function setUp()
    {
        // instantiate memory adapter
        $this->_options = new Cache\Storage\Adapter\MemoryOptions();
        $this->_storage = new Cache\Storage\Adapter\Memory();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['memory'],
            ['Memory'],
        ];
    }

    public function testThrowOutOfSpaceException()
    {
        $this->_options->setMemoryLimit(memory_get_usage(true) - 8);

        $this->expectException('Laminas\Cache\Exception\OutOfSpaceException');
        $this->_storage->addItem('test', 'test');
    }
}
