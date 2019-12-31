<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

/**
 * @group      Laminas_Cache
 */
class DbaQdbmTest extends AbstractDbaTest
{
    protected $handler = 'qdbm';
}
