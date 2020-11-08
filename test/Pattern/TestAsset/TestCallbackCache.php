<?php

namespace LaminasTest\Cache\Pattern\TestAsset;

/**
 * @covers \Laminas\Cache\Pattern\CallbackCache<extended>
 */
final class TestCallbackCache
{
    /**
     * A counter how oftern the method "foo" was called
     */
    public static $fooCounter = 0;

    public static function bar()
    {
        ++static::$fooCounter;
        $args = func_get_args();

        echo   'foobar_output('.implode(', ', $args) . ') : ' . static::$fooCounter;
        return 'foobar_return('.implode(', ', $args) . ') : ' . static::$fooCounter;
    }

    public static function emptyMethod()
    {
    }
}
