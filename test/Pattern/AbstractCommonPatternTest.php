<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\Pattern\PatternOptions;
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
     *
     * @psalm-return non-empty-array<non-empty-string,array{0:non-empty-string}>
     */
    abstract public function getCommonPatternNamesProvider(): array;

    public function testOptionNamesValid(): void
    {
        $options = $this->pattern->getOptions();
        $this->assertInstanceOf(PatternOptions::class, $options);
    }
}
