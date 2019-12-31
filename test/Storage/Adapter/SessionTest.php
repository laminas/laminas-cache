<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Session\Config\StandardConfig as SessionConfig;
use Laminas\Session\Container as SessionContainer;
use LaminasTest\Session\TestAsset\TestManager as TestSessionManager;

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 */
class SessionTest extends CommonAdapterTest
{

    public function setUp()
    {
        $_SESSION = array();
        SessionContainer::setDefaultManager(null);
        $sessionConfig    = new SessionConfig(array('storage' => 'Laminas\Session\Storage\ArrayStorage'));
        $sessionManager   = $manager = new TestSessionManager($sessionConfig);
        $sessionContainer = new SessionContainer('Default', $manager);

        $this->_options = new Cache\Storage\Adapter\SessionOptions(array(
            'session_container' => $sessionContainer
        ));
        $this->_storage = new Cache\Storage\Adapter\Session();
        $this->_storage->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown()
    {
        $_SESSION = array();
        SessionContainer::setDefaultManager(null);
    }
}
