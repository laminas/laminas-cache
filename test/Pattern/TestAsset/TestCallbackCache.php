<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern\TestAsset;

use function func_get_args;
use function implode;

/**
 * @covers \Laminas\Cache\Pattern\CallbackCache<extended>
 */
final class TestCallbackCache
{
    /**
     * A counter how oftern the method "foo" was called
     *
     * @var int
     */
    public static $fooCounter = 0;

    public static function bar(): string
    {
        ++static::$fooCounter;
        $args = func_get_args();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $imploded = implode(', ', $args);
        echo 'foobar_output(' . $imploded . ') : ' . static::$fooCounter;
        return 'foobar_return(' . $imploded . ') : ' . static::$fooCounter;
    }

    public static function emptyMethod()
    {
    }
}
