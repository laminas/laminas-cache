<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\PatternOptions<extended>
 */
abstract class AbstractCommonPatternTest extends TestCase
{
    /** @var PatternInterface */
    protected $pattern;

    protected function setUp(): void
    {
        self::assertInstanceOf(
            PatternInterface::class,
            $this->pattern,
            'Internal pattern instance is needed for tests'
        );

        parent::setUp();
    }

    public function tearDown(): void
    {
        unset($this->pattern);
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
        $pluginManager = new PatternPluginManager(new ServiceManager());
        $this->assertTrue(
            $pluginManager->has($commonPatternName),
            "Pattern name '{$commonPatternName}' not found in PatternPluginManager"
        );
    }

    public function testOptionNamesValid()
    {
        $options = $this->pattern->getOptions();
        $this->assertInstanceOf(PatternOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault()
    {
        $options = $this->pattern->getOptions();
        $this->pattern->setOptions($options);
        $this->assertSame($options, $this->pattern->getOptions());
    }
}
