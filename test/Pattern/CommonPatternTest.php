<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\ServiceManager;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\PatternOptions<extended>
 */
abstract class CommonPatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Laminas\Cache\Pattern\PatternInterface
     */
    protected $_pattern;

    public function setUp()
    {
        $this->assertInstanceOf(
            'Laminas\Cache\Pattern\PatternInterface',
            $this->_pattern,
            'Internal pattern instance is needed for tests'
        );
    }

    public function tearDown()
    {
        unset($this->_pattern);
    }

    /**
     * A data provider for common pattern names
     */
    abstract public function getCommonPatternNamesProvider();

    /**
     * @dataProvider getCommonPatternNamesProvider
     */
    public function testPatternPluginManagerWithCommonNames($commonPatternName)
    {
        $pluginManager = new PatternPluginManager(new ServiceManager);
        $this->assertTrue(
            $pluginManager->has($commonPatternName),
            "Pattern name '{$commonPatternName}' not found in PatternPluginManager"
        );
    }

    public function testOptionNamesValid()
    {
        $options = $this->_pattern->getOptions();
        $this->assertInstanceOf('Laminas\Cache\Pattern\PatternOptions', $options);
    }

    public function testOptionsGetAndSetDefault()
    {
        $options = $this->_pattern->getOptions();
        $this->_pattern->setOptions($options);
        $this->assertSame($options, $this->_pattern->getOptions());
    }
}
