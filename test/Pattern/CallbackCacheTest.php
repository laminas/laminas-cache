<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\CallbackCache;
use Laminas\Cache\Storage\StorageInterface;
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
 * @template-extends AbstractCommonStoragePatternTest<CallbackCache>
 */
class CallbackCacheTest extends AbstractCommonStoragePatternTest
{
    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);

        $this->pattern = new CallbackCache($this->storage);

        parent::setUp();
    }

    public function getCommonPatternNamesProvider(): array
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

        $this->storage
        ->expects(self::once())
        ->method('getItem')
        ->with($generatedKey, null)
        ->willReturn(null);

        $this->storage
        ->expects(self::once())
        ->method('setItem')
        ->with($generatedKey, self::anything())
        ->willReturn(true);

        $this->pattern->call($callback, $args);
    }

    /**
     * Running tests calling {@see \LaminasTest\Cache\Pattern\TestCallbackCache::bar}
     * using different callbacks resulting in this method call
     *
     * @param callable():string $callback
     * @param array<array-key,string> $args
     */
    protected function executeCallbackAndMakeAssertions(callable $callback, array $args): void
    {
        $options     = $this->pattern->getOptions();
        $cacheOutput = $options->getCacheOutput();
        $imploded    = implode(', ', $args);
        $returnSpec  = 'foobar_return(' . $imploded . ') : ';
        $outputSpec  = 'foobar_output(' . $imploded . ') : ';

        // first call - not cached
        $firstCounter = TestCallbackCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(false);

        $expectedKey = $this->pattern->generateKey($callback, $args);
        $this->storage
            ->expects(self::exactly(2))
            ->method('getItem')
            ->with($expectedKey, null)
            ->willReturnCallback(
                function (
                    string $key,
                    bool|null &$success = null
                ) use (
                    $returnSpec,
                    $outputSpec,
                    $firstCounter,
                    $cacheOutput,
                ): ?array {
                    static $called = false;
                    if ($called === true) {
                        $success = true;

                        $cached = [$returnSpec . $firstCounter];
                        if ($cacheOutput) {
                            $cached[] = $outputSpec . $firstCounter;
                        }

                        return $cached;
                    }

                    $called = true;
                    return null;
                }
            );

        $return = $this->pattern->call($callback, $args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - "cached"
        ob_start();
        ob_implicit_flush(false);

        $return = $this->pattern->call($callback, $args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($cacheOutput) {
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
        $this->storage
        ->expects(self::once())
        ->method('getItem')
        ->with($key, null)
        ->willReturnCallback(function (string $key, ?bool &$success = null): array {
            $success = true;
            return [null];
        });

        $value = $this->pattern->call($callback);
        self::assertNull($value);
    }
}
