<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\PatternOptions<extended>
 */
abstract class AbstractCommonPatternTestCase extends TestCase
{
    /** @var PatternInterface */
    protected $pattern;

    public function setUp(): void
    {
        self::assertInstanceOf(
            PatternInterface::class,
            $this->pattern,
            'Internal pattern instance is needed for tests'
        );
    }

    public function tearDown(): void
    {
        unset($this->pattern);
        parent::tearDown();
    }

    /**
     * A data provider for common pattern names
     *
     * @return iterable<string,array{0:string}>
     */
    abstract public function getCommonPatternNamesProvider();

    /**
     * @dataProvider getCommonPatternNamesProvider
     */
    public function testPatternPluginManagerWithCommonNames(string $commonPatternName)
    {
        $pluginManager = new PatternPluginManager(new ServiceManager());
        self::assertTrue(
            $pluginManager->has($commonPatternName),
            "Pattern name '{$commonPatternName}' not found in PatternPluginManager"
        );
    }

    public function testOptionNamesValid(): void
    {
        $options = $this->pattern->getOptions();
        self::assertInstanceOf(PatternOptions::class, $options);
    }

    public function testOptionsGetAndSetDefault(): void
    {
        $options = $this->pattern->getOptions();
        $this->pattern->setOptions($options);
        self::assertSame($options, $this->pattern->getOptions());
    }
}
