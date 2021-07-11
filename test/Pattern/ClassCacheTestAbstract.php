<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\TestClassCache;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\ClassCache<extended>
 */
class ClassCacheTestAbstract extends AbstractCommonStoragePatternTest
{

    protected function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'class'   => TestClassCache::class,
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\ClassCache($this->storage, $this->options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['class'],
            ['Class'],
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

    protected function doTestCall($method, array $args)
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestClassCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array([$this->pattern, $method], $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $this->assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = call_user_func_array([$this->pattern, $method], $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        if ($this->options->getCacheOutput()) {
            $this->assertEquals($outputSpec . $firstCounter, $data);
        } else {
            $this->assertEquals('', $data);
        }
    }
}
