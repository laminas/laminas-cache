<?php

namespace LaminasTest\Cache\Pattern\TestAsset;

/**
 * @covers \Laminas\Cache\Pattern\ObjectCache<extended>
 */
final class TestObjectCache
{
    /**
     * A counter how oftern the method "bar" was called
     */
    public static $fooCounter = 0;

    public $property = 'testProperty';

    public function bar()
    {
        ++static::$fooCounter;
        $args = func_get_args();

        echo 'foobar_output('.implode(', ', $args) . ') : ' . static::$fooCounter;
        return 'foobar_return('.implode(', ', $args) . ') : ' . static::$fooCounter;
    }

    public function __invoke()
    {
        return call_user_func_array([$this, 'bar'], func_get_args());
    }

    public function emptyMethod()
    {
    }
}
