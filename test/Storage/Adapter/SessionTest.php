<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Session\Container as SessionContainer;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Session<extended>
 */
class SessionTest extends CommonAdapterTest
{
    public function setUp()
    {
        $_SESSION = [];
        SessionContainer::setDefaultManager(null);
        $sessionContainer = new SessionContainer('Default');

        $this->_options = new Cache\Storage\Adapter\SessionOptions([
            'session_container' => $sessionContainer
        ]);
        $this->_storage = new Cache\Storage\Adapter\Session();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown()
    {
        if (! class_exists(SessionContainer::class)) {
            return;
        }

        $_SESSION = [];
        SessionContainer::setDefaultManager(null);
    }

    public function getCommonAdapterNamesProvider()
    {
        return [
            ['session'],
            ['Session'],
        ];
    }
}
