<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\Dba;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Dba<extended>
 */
class DbaInifileTest extends TestCase
{
    public function testSpecifyingInifileHandlerRaisesException()
    {
        if (! extension_loaded('dba')) {
            $this->markTestSkipped("Missing ext/dba");
        }

        $this->setExpectedException('Laminas\Cache\Exception\ExtensionNotLoadedException', 'inifile');
        $cache = new Dba([
            'pathname' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('laminascache_dba_') . '.inifile',
            'handler'  => 'inifile',
        ]);
    }
}
