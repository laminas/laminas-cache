<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern\TestAsset;

use function func_get_args;
use function implode;

/**
 * @covers \Laminas\Cache\Pattern\ObjectCache<extended>
 */
final class TestObjectCache
{
    /**
     * A counter how oftern the method "bar" was called
     *
     * @var int
     */
    public static $fooCounter = 0;

    /** @var string */
    public $property = 'testProperty';

    public function bar(): string
    {
        ++static::$fooCounter;
        $args = func_get_args();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $imploded = implode(', ', $args);
        echo 'foobar_output(' . $imploded . ') : ' . static::$fooCounter;
        return 'foobar_return(' . $imploded . ') : ' . static::$fooCounter;
    }

    public function __invoke(): string
    {
        $args = func_get_args();
        return $this->bar(...$args);
    }

    public function emptyMethod(): void
    {
    }
}
