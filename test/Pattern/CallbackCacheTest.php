<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\InvalidArgumentException;
use LaminasTest\Cache\Pattern\TestAsset\FailableCallback;
use LaminasTest\Cache\Pattern\TestAsset\TestCallbackCache;

use function func_get_args;
use function implode;
use function ob_get_clean;
use function ob_implicit_flush;
use function ob_start;

/**
 * @see \LaminasTest\Cache\Pattern\Foo::bar
 */
function bar(): string
{
    $args = func_get_args();
    return TestCallbackCache::bar(...$args);
}

/**
 * @group      Laminas_Cache
 */
class CallbackCacheTest extends AbstractCommonStoragePatternTest
{
    protected function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0,
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\CallbackCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPatternNamesProvider()
    {
        return [
            'lowercase' => ['callback'],
            'lcfirst'   => ['Callback'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault(): void
    {
        $this->executeCallbackAndMakeAssertions(
            [TestCallbackCache::class, 'bar'],
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput(): void
    {
        $options = $this->pattern->getOptions();
        $options->setCacheOutput(false);
        $this->executeCallbackAndMakeAssertions(
            [TestCallbackCache::class, 'bar'],
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testMagicFunctionCall(): void
    {
        $this->executeCallbackAndMakeAssertions(
            __NAMESPACE__ . '\bar',
            ['testMagicFunctionCall', 'arg2']
        );
    }

    public function testGenerateKey(): void
    {
        $callback = [TestCallbackCache::class, 'emptyMethod'];
        $args     = ['arg1', 2, 3.33, null];

        $generatedKey = $this->pattern->generateKey($callback, $args);
        $usedKey      = null;
        $this->options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params  = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->pattern->call($callback, $args);
        self::assertEquals($generatedKey, $usedKey);
    }

    public function testCallInvalidCallbackException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pattern->call(1);
    }

    public function testCallUnknownCallbackException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pattern->call('notExiststingFunction');
    }

    /**
     * Running tests calling {@see \LaminasTest\Cache\Pattern\TestCallbackCache::bar}
     * using different callbacks resulting in this method call
     *
     * @param callable $callback
     */
    protected function executeCallbackAndMakeAssertions($callback, array $args): void
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestCallbackCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->call($callback, $args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->call($callback, $args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        $options = $this->pattern->getOptions();
        if ($options->getCacheOutput()) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }

    /**
     * @group 4629
     */
    public function testCallCanReturnCachedNullValues(): void
    {
        $callback = new FailableCallback();
        $key      = $this->pattern->generateKey($callback, []);
        $this->storage->setItem($key, [null]);
        $value = $this->pattern->call($callback);
        self::assertNull($value);
    }
}
