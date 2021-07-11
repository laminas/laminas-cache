<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\TestObjectCache;

/**
 * @group      Laminas_Cache
 */
class ObjectCacheTestAbstract extends AbstractCommonStoragePatternTest
{
    protected function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'object'  => new TestObjectCache(),
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\ObjectCache($this->storage, $this->options);

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

    public function testCallEnabledCacheOutputByDefault()
    {
        $this->doTestCall(
            'bar',
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput()
    {
        $this->options->setCacheOutput(false);
        $this->doTestCall(
            'bar',
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testCallInvoke()
    {
        $this->options->setCacheOutput(false);
        $this->doTestCall('__invoke', ['arg1', 'arg2']);
    }

    public function testGenerateKey()
    {
        $args = ['arg1', 2, 3.33, null];

        $generatedKey = $this->pattern->generateKey('emptyMethod', $args);
        $usedKey      = null;
        $this->options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->pattern->call('emptyMethod', $args);
        $this->assertEquals($generatedKey, $usedKey);
    }

    public function testSetProperty()
    {
        $this->pattern->property = 'testSetProperty';
        $this->assertEquals('testSetProperty', $this->options->getObject()->property);
    }

    public function testGetProperty()
    {
        $this->assertEquals($this->options->getObject()->property, $this->pattern->property);
    }

    public function testIssetProperty()
    {
        $this->assertTrue(isset($this->pattern->property));
        $this->assertFalse(isset($this->pattern->unknownProperty));
    }

    public function testUnsetProperty()
    {
        unset($this->pattern->property);
        $this->assertFalse(isset($this->pattern->property));
    }

    /**
     * @group 7039
     */
    public function testEmptyObjectKeys()
    {
        $this->options->setObjectKey('0');
        $this->assertSame('0', $this->options->getObjectKey(), "Can't set string '0' as object key");

        $this->options->setObjectKey('');
        $this->assertSame('', $this->options->getObjectKey(), "Can't set an empty string as object key");

        $this->options->setObjectKey(null);
        $this->assertSame(get_class($this->options->getObject()), $this->options->getObjectKey());
    }

    protected function doTestCall($method, array $args)
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';
        $callback   = [$this->pattern, $method];

        // first call - not cached
        $firstCounter = TestObjectCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array($callback, $args);
        $data = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $this->assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array($callback, $args);
        $data = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        if ($this->options->getCacheOutput()) {
            $this->assertEquals($outputSpec . $firstCounter, $data);
        } else {
            $this->assertEquals('', $data);
        }
    }
}
