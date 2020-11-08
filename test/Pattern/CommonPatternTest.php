<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\Pattern\PatternOptions;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\PatternOptions<extended>
 */
abstract class CommonPatternTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @var \Laminas\Cache\Pattern\PatternInterface
     */
    protected $_pattern;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        self::assertInstanceOf(
            PatternInterface::class,
            $this->_pattern,
            'Internal pattern instance is needed for tests'
        );
    }

    public function tearDown(): void
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
        self::assertTrue(
            $pluginManager->has($commonPatternName),
            "Pattern name '{$commonPatternName}' not found in PatternPluginManager"
        );
    }

    public function testOptionNamesValid(): void
    {
        $options = $this->_pattern->getOptions();
        self::assertInstanceOf(PatternOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault(): void
    {
        $options = $this->_pattern->getOptions();
        $this->_pattern->setOptions($options);
        self::assertSame($options, $this->_pattern->getOptions());
    }
}
