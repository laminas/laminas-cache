<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

/**
 * PHPUnit test case
 */

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage UnitTests
 * @group      Laminas_Cache
 */
abstract class CommonPluginTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The storage plugin
     *
     * @var \Laminas\Cache\Storage\Plugin\PluginInterface
     */
    protected $_plugin;

    public function testOptionObjectAvailable()
    {
        $options = $this->_plugin->getOptions();
        $this->assertInstanceOf('Laminas\Cache\Storage\Plugin\PluginOptions', $options);
    }

    public function testOptionsGetAndSetDefault()
    {
        $options = $this->_plugin->getOptions();
        $this->_plugin->setOptions($options);
        $this->assertSame($options, $this->_plugin->getOptions());
    }
}
