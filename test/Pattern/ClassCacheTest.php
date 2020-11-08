<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\TestClassCache;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\ClassCache<extended>
 */
class ClassCacheTest extends CommonPatternTest
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
            'class'   => TestAsset\TestClassCache::class,
            'storage' => $this->_storage,
        ]);
        $this->_pattern = new Cache\Pattern\ClassCache();
        $this->_pattern->setOptions($this->_options);

        parent::setUp();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['class'],
            ['Class'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault(): void
    {
        $this->_testCall(
            'bar',
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput(): void
    {
        $this->_options->setCacheOutput(false);
        $this->_testCall(
            'bar',
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testGenerateKey(): void
    {
        $args = ['arg1', 2, 3.33, null];

        $generatedKey = $this->_pattern->generateKey('emptyMethod', $args);
        $usedKey      = null;
        $this->_options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->_pattern->call('emptyMethod', $args);
        self::assertEquals($generatedKey, $usedKey);
    }

    // @codingStandardsIgnoreStart
    protected function _testCall($method, array $args)
    {
        // @codingStandardsIgnoreEnd
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestClassCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array([$this->_pattern, $method], $args);
        $data = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array([$this->_pattern, $method], $args);
        $data = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($this->_options->getCacheOutput()) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }
}
