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
 * @covers Laminas\Cache\Storage\Adapter\Dba<extended>
 */
abstract class AbstractDbaTest extends CommonAdapterTest
{
    protected $handler;
    protected $temporaryDbaFile;

    public function setUp()
    {
        if (! extension_loaded('dba')) {
            try {
                new Cache\Storage\Adapter\Dba();
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped("Missing ext/dba");
            }
        }

        if (! in_array($this->handler, dba_handlers())) {
            try {
                new Cache\Storage\Adapter\DbaOptions(['handler' => $this->handler]);
                $this->fail("Expected exception Laminas\Cache\Exception\ExtensionNotLoadedException");
            } catch (Cache\Exception\ExtensionNotLoadedException $e) {
                $this->markTestSkipped("Missing ext/dba handler '{$this->handler}'");
            }
        }

        $this->temporaryDbaFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('laminascache_dba_') . '.'
            . $this->handler;
        $this->_options = new Cache\Storage\Adapter\DbaOptions([
            'pathname' => $this->temporaryDbaFile,
            'handler'  => $this->handler,
        ]);

        $this->_storage = new Cache\Storage\Adapter\Dba();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown()
    {
        $this->_storage = null;

        if (file_exists($this->temporaryDbaFile)) {
            unlink($this->temporaryDbaFile);
        }

        parent::tearDown();
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['dba'],
            ['Dba'],
            ['DBA'],
        ];
    }
}
