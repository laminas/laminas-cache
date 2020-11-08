<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\TestObjectCache;

/**
 * @group      Laminas_Cache
 */
class ObjectCacheTest extends CommonPatternTest
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
            'object'  => new TestObjectCache(),
            'storage' => $this->_storage,
        ]);
        $this->_pattern = new Cache\Pattern\ObjectCache();
        $this->_pattern->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['object'],
            ['Object'],
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

    public function testCallInvoke(): void
    {
        $this->_options->setCacheOutput(false);
        $this->_testCall('__invoke', ['arg1', 'arg2']);
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

    public function testSetProperty(): void
    {
        $this->_pattern->property = 'testSetProperty';
        self::assertEquals('testSetProperty', $this->_options->getObject()->property);
    }

    public function testGetProperty(): void
    {
        self::assertEquals($this->_options->getObject()->property, $this->_pattern->property);
    }

    public function testIssetProperty(): void
    {
        self::assertTrue(isset($this->_pattern->property));
        self::assertFalse(isset($this->_pattern->unknownProperty));
    }

    public function testUnsetProperty(): void
    {
        unset($this->_pattern->property);
        self::assertFalse(isset($this->_pattern->property));
    }

    /**
     * @group 7039
     */
    public function testEmptyObjectKeys(): void
    {
        $this->_options->setObjectKey('0');
        self::assertSame('0', $this->_options->getObjectKey(), "Can't set string '0' as object key");

        $this->_options->setObjectKey('');
        self::assertSame('', $this->_options->getObjectKey(), "Can't set an empty string as object key");

        $this->_options->setObjectKey(null);
        self::assertSame(get_class($this->_options->getObject()), $this->_options->getObjectKey());
    }

    // @codingStandardsIgnoreStart
    protected function _testCall($method, array $args)
    {
        // @codingStandardsIgnoreEnd
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';
        $callback   = [$this->_pattern, $method];

        // first call - not cached
        $firstCounter = TestObjectCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array($callback, $args);
        $data = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array($callback, $args);
        $data = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($this->_options->getCacheOutput()) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }
}
