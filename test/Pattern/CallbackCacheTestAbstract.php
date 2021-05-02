<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\FailableCallback;
use LaminasTest\Cache\Pattern\TestAsset\TestCallbackCache;
use Laminas\Cache\Exception\InvalidArgumentException;

/**
 * Test function
 * @see LaminasTest\Cache\Pattern\Foo::bar
 */
function bar()
{
    return call_user_func_array(__NAMESPACE__ . '\TestAsset\TestCallbackCache::bar', func_get_args());
}

/**
 * @group      Laminas_Cache
 */
class CallbackCacheTestAbstract extends AbstractCommonStoragePatternTest
{
    protected function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\CallbackCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['callback'],
            ['Callback'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault()
    {
        $this->doTestCall(
            __NAMESPACE__ . '\TestAsset\TestCallbackCache::bar',
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput()
    {
        $options = $this->pattern->getOptions();
        $options->setCacheOutput(false);
        $this->doTestCall(
            __NAMESPACE__ . '\TestAsset\TestCallbackCache::bar',
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testMagicFunctionCall()
    {
        $this->doTestCall(
            __NAMESPACE__ . '\bar',
            ['testMagicFunctionCall', 'arg2']
        );
    }

    public function testGenerateKey()
    {
        $callback = __NAMESPACE__ . '\TestAsset\TestCallbackCache::emptyMethod';
        $args     = ['arg1', 2, 3.33, null];

        $generatedKey = $this->pattern->generateKey($callback, $args);
        $usedKey      = null;
        $this->options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->pattern->call($callback, $args);
        $this->assertEquals($generatedKey, $usedKey);
    }

    public function testCallInvalidCallbackException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pattern->call(1);
    }

    public function testCallUnknownCallbackException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pattern->call('notExiststingFunction');
    }

    /**
     * Running tests calling LaminasTest\Cache\Pattern\TestCallbackCache::bar
     * using different callbacks resulting in this method call
     */
    protected function doTestCall($callback, array $args)
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestCallbackCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->call($callback, $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $this->assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->call($callback, $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $options = $this->pattern->getOptions();
        if ($options->getCacheOutput()) {
            $this->assertEquals($outputSpec . $firstCounter, $data);
        } else {
            $this->assertEquals('', $data);
        }
    }

    /**
     * @group 4629
     * @return void
     */
    public function testCallCanReturnCachedNullValues()
    {
        $callback = new FailableCallback();
        $key      = $this->pattern->generateKey($callback, []);
        $this->storage->setItem($key, [null]);
        $value    = $this->pattern->call($callback);
        $this->assertNull($value);
    }
}
