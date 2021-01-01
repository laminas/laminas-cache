<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\FailableCallback;
use LaminasTest\Cache\Pattern\TestAsset\TestCallbackCache;
use Laminas\Cache\Exception\InvalidArgumentException;
use function func_get_args;

/**
 * @see \LaminasTest\Cache\Pattern\Foo::bar
 */
function bar()
{
    $args = func_get_args();
    return TestCallbackCache::bar(...$args);
}

/**
 * @group      Laminas_Cache
 */
class CallbackCacheTest extends CommonPatternTest
{
    // @codingStandardsIgnoreStart
    /**
     * @var \Laminas\Cache\Storage\StorageInterface
     */
    protected $_storage;

    /**
     * @var Cache\Pattern\PatternOptions
     */
    private $_options;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        $this->_storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->_options = new Cache\Pattern\PatternOptions([
            'storage' => $this->_storage,
        ]);
        $this->_pattern = new Cache\Pattern\CallbackCache();
        $this->_pattern->setOptions($this->_options);

        parent::setUp();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['callback'],
            ['Callback'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault(): void
    {
        $this->_testCall(
            [TestCallbackCache::class, 'bar'],
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput(): void
    {
        $options = $this->_pattern->getOptions();
        $options->setCacheOutput(false);
        $this->_testCall(
            [TestCallbackCache::class, 'bar'],
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testMagicFunctionCall(): void
    {
        $this->_testCall(
            __NAMESPACE__ . '\bar',
            ['testMagicFunctionCall', 'arg2']
        );
    }

    public function testGenerateKey(): void
    {
        $callback = [TestCallbackCache::class, 'emptyMethod'];
        $args     = ['arg1', 2, 3.33, null];

        $generatedKey = $this->_pattern->generateKey($callback, $args);
        $usedKey      = null;
        $this->_options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->_pattern->call($callback, $args);
        self::assertEquals($generatedKey, $usedKey);
    }

    public function testCallInvalidCallbackException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->_pattern->call(1);
    }

    public function testCallUnknownCallbackException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->_pattern->call('notExiststingFunction');
    }

    /**
     * Running tests calling {@see \LaminasTest\Cache\Pattern\TestCallbackCache::bar}
     * using different callbacks resulting in this method call
     */
    // @codingStandardsIgnoreStart
    protected function _testCall($callback, array $args)
    {
        // @codingStandardsIgnoreEnd
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestCallbackCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $this->_pattern->call($callback, $args);
        $data = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $this->_pattern->call($callback, $args);
        $data = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        $options = $this->_pattern->getOptions();
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
        $key      = $this->_pattern->generateKey($callback, []);
        $this->_storage->setItem($key, [null]);
        $value    = $this->_pattern->call($callback);
        self::assertNull($value);
    }
}
